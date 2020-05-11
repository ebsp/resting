<?php

namespace Seier\Resting\Support;

use Illuminate\Http\JsonResponse;
use ReflectionClass;
use ReflectionParameter;
use Seier\Resting\Query;
use Seier\Resting\Params;
use Illuminate\Support\Arr;
use Seier\Resting\Resource;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Seier\Resting\Fields\FieldAbstract;
use Seier\Resting\Fields\ResourceField;
use Illuminate\Routing\RouteCollection;
use Illuminate\Contracts\Support\Arrayable;
use Seier\Resting\Fields\ResourceArrayField;
use Illuminate\Contracts\Support\Responsable;
use Seier\Resting\UnionResource;

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
                return $field instanceof FieldAbstract;
            });

            $fields->each(function (FieldAbstract $abstract, $key) use ($query, $where) {
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
            $field = $attr instanceof FieldAbstract;

            if ($attr instanceof ResourceField) {
                $class = get_class($attr->get()->original());
                if ($class !== UnionResource::class && (new ReflectionClass($class))->getParentClass()->getName() !== UnionResource::class) {
                    $this->describeResource($attr->get()->original());
                }
            } elseif ($attr instanceof ResourceArrayField) {
                $class = get_class($attr->resources());
                if ($class !== UnionResource::class && (new ReflectionClass($class))->getParentClass()->getName() !== UnionResource::class) {
                    $this->describeResource($attr->resources());
                }
            }

            return $field;
        });

        $requiredFields = $fields->filter(function (FieldAbstract $field) {
            return $field->isRequired();
        });

        $this->document['components']['schemas'][static::resourceRefName(get_class($resource))] = [
            'type' => 'object',
            'required' => $requiredFields->map(function (FieldAbstract $field, $key) {
                return $key;
            })->values()->toArray(),
            'properties' => $fields->map(function (FieldAbstract $field) {
                foreach ($field->nestedRefs() as $type => $refs) {
                    if ('schema' === $type) {
                        foreach ((array)$refs as $ref)
                            $this->addResource($ref);
                    } elseif ('parameters' === $type) {
                        foreach ((array)$refs as $ref)
                            $this->addParameter($ref);
                    }
                }

                return $field->type();
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

    protected function describeEndpoint(Route $route, $method)
    {
        $endpoint = [
            'description' => $route->_docs ?? null,
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
                return $field instanceof FieldAbstract;
            })->map(function (FieldAbstract $fieldAbstract, $key) use ($query) {
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
                return $field instanceof FieldAbstract;
            })->map(function (FieldAbstract $fieldAbstract, $key) use ($query) {
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
        $response = [];
        $type = null;
        $classes = [];

        if ($type = $route->action['controller'] ?? null) {
            list($_class, $_method) = explode('@', $type);
            $reflectionClass = new ReflectionClass($_class);
            $return = $reflectionClass->getMethod($_method)->getReturnType();
            if ($return)
                $classes[] = $return->getName();
        } elseif ($route->action['uses'] instanceof \Closure) {
            $reflectionFunction = new \ReflectionFunction($route->action['uses']);
            $return = $reflectionFunction->getReturnType();
            if ($return)
                $classes[] = $return->getName();
        }

        foreach ((array)$route->_lists as $type) {
            $classes[] = $type;
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

            $response = [
                'schema' => $lists ? [
                    'type' => 'array',
                    'items' => [
                        'oneOf' => $refs
                    ],
                ] : $refs[0],
            ];
        }

        return $response;
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
                if ($field instanceof ResourceField && $field->get() instanceof UnionResource) {
                    foreach ($field->get()->getDependantResources() as $dependantResource) {
                        $this->addResource($dependantResource);
                    }
                }
                if ($field instanceof ResourceArrayField && $field->resources() instanceof UnionResource) {
                    foreach ($field->resources()->getDependantResources() as $dependantResource) {
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
