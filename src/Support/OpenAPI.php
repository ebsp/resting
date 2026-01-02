<?php

namespace Seier\Resting\Support;

use ReflectionClass;
use ReflectionParameter;
use Seier\Resting\Query;
use ReflectionNamedType;
use Seier\Resting\Params;
use Illuminate\Support\Arr;
use Seier\Resting\Resource;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Seier\Resting\Fields\Field;
use Seier\Resting\UnionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\RouteCollection;
use Seier\Resting\Fields\ResourceField;
use Illuminate\Contracts\Support\Arrayable;
use Seier\Resting\Fields\ResourceArrayField;
use Illuminate\Contracts\Support\Responsable;

class OpenAPI implements Arrayable, Responsable
{

    public $document;
    protected $routes;
    protected $resources = [];
    protected $parameters = [];

    public function __construct(RouteCollection $collection)
    {
        $this->routes = $collection;

        $this->process();
    }

    protected function process()
    {
        $this->processInfo();

        $this->processPaths();
        $this->processResources();
        $this->processParameters();
    }

    protected function processInfo()
    {
        $this->document['openapi'] = '3.0.0';

        $this->document['info'] = [
            'version' => (string)config('resting.version'),
            'title' => config('resting.api_name'),
        ];

        if ($servers = config('resting.documentation.servers')) {
            $this->document['servers'] = $servers;
        }
    }

    protected function processParameters()
    {
        foreach ($this->parameters as $query => $where) {
            /** @var Resource $query */
            $query = new $query;

            $fields = $query->fields()->filter(function ($field) {
                return $field instanceof Field;
            });

            $fields->each(function (Field $abstract, $key) use ($query, $where) {
                $this->document['components']['parameters'][static::parametersRefName(get_class($query), $key)] = [
                    'in' => $where,
                    'name' => $key,
                    'required' => $abstract->isRequired(),
                    'schema' => $abstract->type(),
                ];
            });
        }
    }

    protected function processResources()
    {
        foreach ($this->resources as $resource => $_) {
            $resource = new $resource;
            $this->describeResource($resource);
        }
    }

    protected function describeResource(Resource $resource)
    {
        $fields = $resource->fields()->filter(function ($attr) {
            $field = $attr instanceof Field;

            if ($attr instanceof ResourceField) {
                $class = get_class($attr->getResourcePrototype());
                if ($class !== UnionResource::class && (new ReflectionClass($class))->getParentClass()->getName() !== UnionResource::class) {
                    $this->describeResource($attr->getResourcePrototype());
                }
            } elseif ($attr instanceof ResourceArrayField) {
                $class = get_class($attr->resource());
                if ($class !== UnionResource::class && (new ReflectionClass($class))->getParentClass()->getName() !== UnionResource::class) {
                    $this->describeResource($attr->resource());
                }
            }

            return $field;
        });

        $requiredFields = $fields->filter(function (Field $field) {
            return $field->isRequired();
        });

        $unionDiscriminatorKey = $resource instanceof UnionResource
            ? $resource->getDiscriminatorKey()
            : null;

        $this->document['components']['schemas'][static::resourceRefName(get_class($resource))] = [
            'type' => 'object',
            'required' => $requiredFields->map(function (Field $field, $key) {
                return $key;
            })->values()->toArray(),
            'properties' => $fields->map(function (Field $field, string $fieldName) use ($resource, $unionDiscriminatorKey) {

                if ($resource instanceof UnionResource && $fieldName === $unionDiscriminatorKey) {
                    $resourceMap = $resource->getResourceMap();
                    $foundDiscriminatorValue = null;
                    foreach ($resourceMap as $resourceMapKey => $resourceMapValue) {
                        if ($resourceMapValue instanceof $resource) {
                            $foundDiscriminatorValue = $resourceMapKey;
                        }
                    }

                    return array_merge($field->type(), [
                        'enum' => [$foundDiscriminatorValue],
                    ]);
                }

                foreach ($field->nestedRefs() as $type => $refs) {

                    if ('schema' === $type) {
                        foreach ((array)$refs as $ref) {
                            $this->addResource($ref);
                        }
                    } elseif ('parameters' === $type) {
                        foreach ((array)$refs as $ref) {
                            $this->addParameter($ref);
                        }
                    }
                }

                $merge = [];

                if ($validator = $field->getValidator()) {
                    $description = $validator->description();
                    if ($secondary = $validator->getSecondaryValidators()) {
                        $description .= "<br/>The value must also conform to the following:";
                        foreach ($secondary as $val) {
                            $description .= "<br/>&nbsp;&nbsp;- " . $val->description();
                        }
                    }

                    $merge['description'] = $description;
                }

                return $merge + $field->type();

            })->toArray(),
        ];
    }

    protected function processPaths()
    {
        $paths = [];

        foreach ($this->routes->getRoutes() as $route) {
            /** @var $route Route */
            $method = Arr::first(array_filter($route->methods(), function ($method) {
                return !in_array($method, ['OPTIONS', 'HEAD']);
            }));

            $route->bind(new Request);

            $paths['/' . $route->uri()][strtolower($method)] = $this->describeEndpoint($route, $method);
        }

        $this->document['paths'] = $paths;
    }

    protected function describeEndpoint(Route $route, $method): array
    {
        $endpoint = [
            'description' => $route->_docs ?? '',
            'responses' => [
                '200' => [
                    "content" => [
                        "application/json" => $this->describeResponse($route),
                    ]
                ]
            ],
        ];

        $resourceClass = Arr::first($route->signatureParameters(), function (ReflectionParameter $parameter) {

            if (!$type = $parameter->getType()) {
                return false;
            }

            if ($type->isBuiltin()) {
                return false;
            }

            $reflectionClass = new ReflectionClass($type->getName());

            if ($reflectionClass->isInstantiable()) {
                if ($reflectionClass->isSubclassOf(Resource::class) && !$reflectionClass->isSubclassOf(Query::class) && !$reflectionClass->isSubclassOf(Params::class)) {
                    return true;
                }
            }

            return false;
        });

        if (in_array($method, ['POST', 'PATCH', 'PUT']) && $resourceClass) {
            /** @var ReflectionParameter $resourceClass */
            $this->addResource($resourceClass->getType()->getName());
            $resourceName = $resourceClass->getType()->getName();

            if ($this->isUnionSubclass($resourceName)) {
                $schema = [
                    'type' => 'object',
                    'oneOf' => $this->createOneOfArray($resourceName)
                ];
            } else {
                $schema = $ref = [
                    '$ref' => static::componentPath(
                        static::resourceRefName($resourceClass->getType()->getName())
                    ),
                ];
            }

            if ($resourceClass->isVariadic()) {
                if ($this->isUnionSubclass($resourceName)) {
                    $schema = [
                        'type' => 'array',
                        'items' => [
                            'oneOf' => $this->createOneOfArray($resourceName)
                        ]
                    ];
                } else {
                    $schema = [
                        'type' => 'object',
                        'properties' => [
                            'data' => [
                                'type' => 'array',
                                'items' => $ref,
                            ]
                        ]
                    ];
                }
            }

            $endpoint['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => $schema,
                    ],
                ],
            ];
        }

        $queryClass = Arr::first($route->signatureParameters(), function (ReflectionParameter $parameter) {

            if (!$type = $parameter->getType()) {
                return false;
            }

            if ($type->isBuiltin()) {
                return false;
            }

            $reflectionClass = new ReflectionClass($type->getName());

            if ($reflectionClass->isInstantiable()) {
                if ($reflectionClass->isSubclassOf(Query::class)) {
                    return true;
                }
            }

            return false;
        });

        if ($queryClass) {
            /** @var ReflectionParameter $queryClass */
            $this->addParameter($queryClass = $queryClass->getType()->getName());
            /** @var Query $query */
            $query = new $queryClass;

            $fields = $query->fields()->filter(function ($field) {
                return $field instanceof Field;
            })->map(function (Field $fieldAbstract, $key) use ($query) {
                return [
                    '$ref' => static::componentPath(
                        static::parametersRefName(get_class($query), $key), 'parameters'
                    ),
                ];
            })->values();

            $endpoint['parameters'] = $fields->toArray();
        }

        $paramClass = Arr::first($route->signatureParameters(), function (ReflectionParameter $parameter) {

            if (!$type = $parameter->getType()) {
                return false;
            }

            if ($type->isBuiltin()) {
                return false;
            }

            $reflectionClass = new ReflectionClass($type->getName());

            if ($reflectionClass->isInstantiable()) {
                if ($reflectionClass->isSubclassOf(Params::class)) {
                    return true;
                }
            }

            return false;
        });

        if ($paramClass) {
            /** @var ReflectionParameter $queryClass */
            $this->addParameter($paramClass = $paramClass->getType()->getName(), 'path');
            /** @var Params $query */
            $query = new $paramClass;

            $fields = $query->fields()->filter(function ($field) {
                return $field instanceof Field;
            })->map(function (Field $fieldAbstract, $key) use ($query) {
                return [
                    '$ref' => static::componentPath(
                        static::parametersRefName(get_class($query), $key), 'parameters'
                    )
                ];
            })->values();

            $endpoint['parameters'] = array_merge($endpoint['parameters'] ?? [], $fields->toArray());
        }

        return $endpoint;
    }

    protected function describeResponse(Route $route)
    {
        $classes = [];
        $responseType = [];
        $returnType = null;

        if ($type = $route->action['controller'] ?? null) {
            list($_class, $_method) = explode('@', $type);
            $reflectionClass = new ReflectionClass($_class);
            $returnType = $reflectionClass->getMethod($_method)->getReturnType();
        } elseif ($route->action['uses'] instanceof \Closure) {
            $reflectionFunction = new \ReflectionFunction($route->action['uses']);
            $returnType = $reflectionFunction->getReturnType();
        }

        foreach ((array)$route->_lists as $type) {
            $classes[] = $type;
        }

        if ($returnType && $returnType->isBuiltin()) {
            $responseType = ['schema' => [
                'nullable' => $returnType->allowsNull(),
                'description' => '',
                ...(match ($returnType->getName()) {
                    'string' => ['type' => 'string'],
                    'float' => [
                        'type' => 'number',
                        'format' => 'double',
                    ],
                    'bool' => ['type' => 'boolean'],
                    'array' => ['type' => 'array', 'items' => []],
                    'object' => ['type' => 'object'],
                    'int' => [
                        'type' => 'integer',
                        'format' => 'int64'
                    ],
                    default => [],
                })
            ]];
        } else if ($returnType instanceof ReflectionNamedType && (new ReflectionClass($returnType->getName()))->isSubclassOf(Resource::class)) {
            $classes[] = $returnType->getName();
        }

        $transform = $classes;
        $classes = [];

        foreach ($transform as $class) {
            if ($this->isUnionSubclass($class)) {
                foreach ($this->getDependantResources($class) as $dependantResource) {
                    $classes[] = $dependantResource;
                }
            } else {
                $classes[] = $class;
            }
        }

        $lists = count($classes) > 1;

        if (count($classes)) {
            foreach ($classes as $className) {
                $this->addResource($className);
            }

            $refs = array_map(function ($_className) {
                return ['$ref' => static::componentPath(static::resourceRefName($_className))];
            }, array_unique($classes));

            $responseType = [
                'schema' => $lists ? [
                    'type' => 'array',
                    'items' => [
                        'oneOf' => $refs
                    ],
                ] : $refs[0],
            ];
        }

        return $responseType;
    }

    protected function isUnionSubclass($className)
    {
        return (new ReflectionClass($className))->isSubclassOf(UnionResource::class);
    }

    protected function createOneOfArray($className)
    {
        return array_values(array_map(function (string $dependant) {
            return ['$ref' => static::componentPath(static::resourceRefName($dependant))];
        }, $this->getDependantResources($className)));
    }

    protected function getDependantResources($className)
    {
        return (new $className)->getDependantResources();
    }

    protected function addResource($resourceName)
    {
        if ($resourceName !== UnionResource::class) {
            $resource = new $resourceName;
            if ((new ReflectionClass($resourceName))->getParentClass()->getName() === UnionResource::class) {
                foreach ($resource->getDependantResources() as $unionType) {
                    $this->addResource($unionType);
                }
            } else {
                $this->resources[$resourceName] = [];
            }

            foreach ($resource->fields() as $k => $field) {
                if ($field instanceof ResourceField && ($field->getResourcePrototype() instanceof UnionResource)) {
                    foreach ($field->getResourcePrototype()->getDependantResources() as $dependantResource) {
                        $this->addResource($dependantResource);
                    }
                }
                if ($field instanceof ResourceArrayField && $field->resource() instanceof UnionResource) {
                    foreach ($field->resource()->getDependantResources() as $dependantResource) {
                        $this->addResource($dependantResource);
                    }
                }
            }
        }
    }

    public function addParameter($queryClass, $where = 'query')
    {
        $this->parameters[$queryClass] = $where;
    }

    public static function componentPath($component, $type = 'schemas')
    {
        return "#/components/{$type}/{$component}";
    }

    public static function resourceRefName($resourceClass)
    {
        return str_replace(['App\\Api\\Resources\\', '\\'], ['', '_'], $resourceClass);
    }

    protected static function parametersRefName($queryClass, $propertyName)
    {
        return str_replace(['App\\Api\\Resources\\', '\\'], ['', '_'], $queryClass) . '_' . $propertyName;
    }

    public function toArray()
    {
        return $this->document;
    }

    public function toResponse($request)
    {
        return new JsonResponse(
            $this->toArray()
        );
    }
}
