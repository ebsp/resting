<?php

namespace Seier\Resting\Support;

use Closure;
use ArrayObject;
use ReflectionType;
use ReflectionClass;
use ReflectionFunction;
use ReflectionParameter;
use Seier\Resting\Query;
use ReflectionNamedType;
use ReflectionUnionType;
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

    public array $document = [];
    protected RouteCollection $routes;
    protected array $resources = [];
    protected array $parameters = [];

    public function __construct(RouteCollection $collection)
    {
        $this->routes = $collection;

        $this->process();
    }

    protected function process(): void
    {
        $this->processInfo();

        $this->processPaths();
        $this->processResources();
        $this->processParameters();
    }

    protected function processInfo(): void
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

    protected function processParameters(): void
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

    protected function processResources(): void
    {
        foreach ($this->resources as $resource => $_) {
            $resource = new $resource;
            $this->describeResource($resource);
        }
    }

    protected function describeResource(Resource $resource): void
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

    protected function processPaths(): void
    {
        $paths = [];

        foreach ($this->routes->getRoutes() as $route) {
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

    protected function describeResponse(Route $route): array
    {
        $resourceClassesSeen = new ArrayObject();
        $responseType = [];
        $returnType = null;

        if ($type = $route->action['controller'] ?? null) {
            list($_class, $_method) = explode('@', $type);
            $reflectionClass = new ReflectionClass($_class);
            $returnType = $reflectionClass->getMethod($_method)->getReturnType();
        } elseif ($route->action['uses'] instanceof Closure) {
            $reflectionFunction = new ReflectionFunction($route->action['uses']);
            $returnType = $reflectionFunction->getReturnType();
        }

        foreach ((array)$route->_lists as $type) {
            $resourceClassesSeen[] = $type;
        }

        if ($returnType instanceof ReflectionType) {
            $responseType = $this->createTypeFromReflectionType($returnType, $resourceClassesSeen);
        }

        foreach ($resourceClassesSeen as $resourceClass) {
            if ($this->isUnionSubclass($resourceClass)) {
                foreach ($this->getDependantResources($resourceClass) as $dependantResource) {
                    $this->addResource($dependantResource);
                }
            } else {
                $this->addResource($resourceClass);

            }
        }

        return ['schema' => $responseType];
    }

    protected function createTypeFromReflectionType(ReflectionType $type, ArrayObject $resourceClassesSeen): array
    {
        if ($type instanceof ReflectionUnionType) {
            return array(
                'nullable' => $type->allowsNull(),
                'oneOf' => array_map(
                    fn (ReflectionType $reflectionType) => $this->createTypeFromReflectionType($reflectionType, $resourceClassesSeen),
                    array_filter(
                        $type->getTypes(),
                        function (ReflectionType $type) {

                            if ($type instanceof ReflectionNamedType && $type->getName() === 'null') {
                                return false;
                            }

                            return true;
                        },
                    ),
                )
            );
        }

        if ($type instanceof ReflectionNamedType) {
            if ($type->isBuiltin()) {
                return [
                    'type' => 'object',
                    'nullable' => $type->allowsNull(),
                    'description' => '',
                    ...(match ($type->getName()) {
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
                ];
            } else if ((new ReflectionClass($className = $type->getName()))->isSubclassOf(Resource::class)) {
                $resourceClassesSeen[] = $className;
                return [
                    'type' => 'object',
                    '$ref' => static::componentPath(static::resourceRefName($className)),
                ];
            }
        }

        return [];
    }

    protected function isUnionSubclass($className): bool
    {
        return (new ReflectionClass($className))->isSubclassOf(UnionResource::class);
    }

    protected function createOneOfArray($className): array
    {
        return array_values(array_map(function (string $dependant) {
            return ['$ref' => static::componentPath(static::resourceRefName($dependant))];
        }, $this->getDependantResources($className)));
    }

    protected function getDependantResources($className)
    {
        return (new $className)->getDependantResources();
    }

    protected function addResource($resourceName): void
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

            foreach ($resource->fields() as $field) {
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

    public function addParameter($queryClass, $where = 'query'): void
    {
        $this->parameters[$queryClass] = $where;
    }

    public static function componentPath($component, $type = 'schemas'): string
    {
        return "#/components/$type/$component";
    }

    public static function resourceRefName($resourceClass): array|string
    {
        return str_replace(['App\\Api\\Resources\\', '\\'], ['', '_'], $resourceClass);
    }

    protected static function parametersRefName($queryClass, $propertyName): string
    {
        return str_replace(['App\\Api\\Resources\\', '\\'], ['', '_'], $queryClass) . '_' . $propertyName;
    }

    public function toArray(): array
    {
        return $this->document;
    }

    public function toResponse($request): JsonResponse
    {
        return new JsonResponse(
            $this->toArray()
        );
    }
}
