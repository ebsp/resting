<?php

namespace Seier\Resting\Support;

use Closure;
use stdClass;
use ArrayObject;
use ReflectionType;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionParameter;
use ReflectionFunctionAbstract;
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
use Seier\Resting\Annotations\Doc;
use Seier\Resting\Annotations\Lists;
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

        $this->document = $this->normalizeRefSiblings($this->document);
        $this->document = $this->normalizeEmptySchemas($this->document);
        $this->pruneUnusedComponents();
    }

    protected function emptySchema(): stdClass
    {
        return new stdClass();
    }

    protected function normalizeTypeReturn(array $type): array|stdClass
    {
        return $type === []
            ? $this->emptySchema()
            : $type;
    }

    protected function normalizeRefSiblings(mixed $node): mixed
    {
        if (is_array($node)) {
            if (isset($node['$ref'])) {
                return ['$ref' => $node['$ref']];
            }

            $normalized = [];
            foreach ($node as $key => $value) {
                $normalized[$key] = $this->normalizeRefSiblings($value);
            }
            return $normalized;
        }

        return $node;
    }

    protected function normalizeEmptySchemas(mixed $node, array $path = []): mixed
    {
        $isSchemaPosition = self::isSchemaPosition($path);

        if (is_array($node) && $node === [] && $isSchemaPosition) {
            return $this->emptySchema();
        }

        if (is_array($node)) {
            $normalized = [];
            foreach ($node as $key => $value) {
                $normalized[$key] = $this->normalizeEmptySchemas($value, [...$path, $key]);
            }
            return $normalized;
        }

        return $node;
    }

    private static function isSchemaPosition(array $path): bool
    {
        $last = end($path);
        if ($last === 'schema' || $last === 'items') {
            return true;
        }

        if (count($path) >= 2 && $path[count($path) - 2] === 'properties') {
            return true;
        }

        if (count($path) >= 2 && ($path[count($path) - 2] === 'oneOf' || $path[count($path) - 2] === 'allOf' || $path[count($path) - 2] === 'anyOf')) {
            return true;
        }

        if (count($path) >= 2 && $path[count($path) - 2] === 'schemas' && count($path) >= 3 && $path[count($path) - 3] === 'components') {
            return true;
        }

        return false;
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
                    'schema' => $this->normalizeTypeReturn($abstract->type()),
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

        $resourceReflection = new ReflectionClass($resource);

        $schema = [
            'type' => 'object',
            'required' => $requiredFields->map(function (Field $field, $key) {
                return $key;
            })->values()->toArray(),
            'properties' => $fields->map(function (Field $field, string $fieldName) use ($resource, $unionDiscriminatorKey, $resourceReflection) {

                $fieldType = $this->normalizeTypeReturn($field->type());

                if ($resource instanceof UnionResource && $fieldName === $unionDiscriminatorKey) {
                    $resourceMap = $resource->getResourceMap();
                    $foundDiscriminatorValue = null;
                    foreach ($resourceMap as $resourceMapKey => $resourceMapValue) {
                        if ($resourceMapValue instanceof $resource) {
                            $foundDiscriminatorValue = $resourceMapKey;
                        }
                    }

                    $base = is_array($fieldType) ? $fieldType : [];
                    if ($foundDiscriminatorValue !== null) {
                        return array_merge($base, ['enum' => [$foundDiscriminatorValue]]);
                    }
                    return $base === [] ? $this->emptySchema() : $base;
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

                $description = null;

                if ($validator = $field->getValidator()) {
                    $description = $validator->description();
                    if ($secondary = $validator->getSecondaryValidators()) {
                        $description .= "<br/>The value must also conform to the following:";
                        foreach ($secondary as $val) {
                            $description .= "<br/>&nbsp;&nbsp;- " . $val->description();
                        }
                    }
                }

                $docDescription = $this->describePropertyDoc($resourceReflection, $fieldName);
                if ($docDescription !== null) {
                    $description = $description
                        ? $docDescription . "\n\n" . $description
                        : $docDescription;
                }

                if ($description !== null) {
                    $merge['description'] = $description;
                }

                if (!is_array($fieldType)) {
                    return $merge === [] ? $this->emptySchema() : $merge;
                }

                if (isset($fieldType['$ref']) && $merge !== []) {
                    return ['allOf' => [$fieldType]] + $merge;
                }

                return $merge + $fieldType;

            })->toArray(),
        ];

        if ($classDoc = Doc::descriptionFor($resourceReflection)) {
            $schema['description'] = $classDoc;
        }

        $this->document['components']['schemas'][static::resourceRefName(get_class($resource))] = $schema;
    }

    protected function describePropertyDoc(ReflectionClass $resourceReflection, string $fieldName): ?string
    {
        $current = $resourceReflection;
        while ($current) {
            if ($current->hasProperty($fieldName)) {
                $property = $current->getProperty($fieldName);
                $description = Doc::descriptionFor($property);
                if ($description !== null) {
                    return $description;
                }
            }
            $current = $current->getParentClass() ?: null;
        }

        return null;
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
        $actionReflection = $this->getRouteActionReflection($route);
        $endpointDescription = $actionReflection ? Doc::descriptionFor($actionReflection) : null;

        $endpoint = [
            'description' => $endpointDescription ?? '',
            'responses' => [
                '200' => [
                    'description' => ($endpointDescription !== null && $endpointDescription !== '') ? $endpointDescription : 'OK',
                    'content' => [
                        'application/json' => $this->describeResponse($route),
                    ],
                ],
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
                $schema = $this->unionResourceSchema($resourceName);
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
                        'items' => $this->unionResourceSchema($resourceName),
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

            $resourceParameterDoc = Doc::descriptionFor($resourceClass);
            if ($resourceParameterDoc !== null) {
                $endpoint['requestBody']['description'] = $resourceParameterDoc;
            }
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

        $coveredPathNames = [];

        if ($paramClass) {
            /** @var ReflectionParameter $queryClass */
            $this->addParameter($paramClass = $paramClass->getType()->getName(), 'path');
            /** @var Params $query */
            $query = new $paramClass;

            $paramFields = $query->fields()->filter(function ($field) {
                return $field instanceof Field;
            });

            foreach ($paramFields->keys() as $name) {
                $coveredPathNames[$name] = true;
            }

            $fields = $paramFields->map(function (Field $fieldAbstract, $key) use ($query) {
                return [
                    '$ref' => static::componentPath(
                        static::parametersRefName(get_class($query), $key), 'parameters'
                    )
                ];
            })->values();

            $endpoint['parameters'] = array_merge($endpoint['parameters'] ?? [], $fields->toArray());
        }

        $scalarParameters = [];
        foreach ($route->signatureParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && $type->isBuiltin()) {
                $scalarParameters[$parameter->getName()] = $parameter;
            }
        }

        foreach ($route->parameterNames() as $placeholder) {
            if (isset($coveredPathNames[$placeholder])) {
                continue;
            }

            $paramEntry = [
                'in' => 'path',
                'name' => $placeholder,
                'required' => true,
                'schema' => ['type' => 'string'],
            ];

            if (isset($scalarParameters[$placeholder])) {
                $scalar = $scalarParameters[$placeholder];
                $paramEntry['schema'] = $this->scalarSchemaForName($scalar->getType()->getName());

                $description = Doc::descriptionFor($scalar);
                if ($description !== null) {
                    $paramEntry['description'] = $description;
                }
            }

            $endpoint['parameters'][] = $paramEntry;
        }

        return $endpoint;
    }

    protected function scalarSchemaForName(string $name): array
    {
        return match ($name) {
            'string' => ['type' => 'string'],
            'int' => ['type' => 'integer', 'format' => 'int64'],
            'float' => ['type' => 'number', 'format' => 'double'],
            'bool' => ['type' => 'boolean'],
            default => ['type' => 'string'],
        };
    }

    protected function describeResponse(Route $route): array
    {
        $resourceClassesSeen = new ArrayObject();
        $responseType = $this->emptySchema();
        $returnType = null;

        $actionReflection = $this->getRouteActionReflection($route);
        if ($actionReflection) {
            $returnType = $actionReflection->getReturnType();

            foreach (Lists::resourcesFor($actionReflection) as $type) {
                $resourceClassesSeen[] = $type;
            }
        }

        if ($returnType instanceof ReflectionType) {
            $reflected = $this->createTypeFromReflectionType($returnType, $resourceClassesSeen);
            if (is_array($reflected) && $reflected === []) {
                $reflected = $this->emptySchema();
            }
            $responseType = $reflected;
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

    protected function getRouteActionReflection(Route $route): ?ReflectionFunctionAbstract
    {
        if ($type = $route->action['controller'] ?? null) {
            if (is_string($type) && str_contains($type, '@')) {
                [$class, $method] = explode('@', $type);
                $reflectionClass = new ReflectionClass($class);
                return $reflectionClass->getMethod($method);
            }
        }

        $uses = $route->action['uses'] ?? null;
        if ($uses instanceof Closure) {
            return new ReflectionFunction($uses);
        }

        if (is_string($uses) && str_contains($uses, '@')) {
            [$class, $method] = explode('@', $uses);
            $reflectionClass = new ReflectionClass($class);
            return $reflectionClass->getMethod($method);
        }

        return null;
    }

    protected function createTypeFromReflectionType(ReflectionType $type, ArrayObject $resourceClassesSeen): array|stdClass
    {
        if ($type instanceof ReflectionUnionType) {
            $members = [];
            foreach ($type->getTypes() as $inner) {
                if ($inner instanceof ReflectionNamedType && $inner->getName() === 'null') {
                    continue;
                }

                $memberSchema = $this->createTypeFromReflectionType($inner, $resourceClassesSeen);

                if (is_array($memberSchema) && $memberSchema === []) {
                    continue;
                }

                $members[] = $memberSchema;
            }

            if ($members === []) {
                return $type->allowsNull()
                    ? ['nullable' => true]
                    : $this->emptySchema();
            }

            return [
                'nullable' => $type->allowsNull(),
                'oneOf' => $members,
            ];
        }

        if ($type instanceof ReflectionNamedType) {
            if ($type->isBuiltin()) {
                return [
                    'nullable' => $type->allowsNull(),
                    'description' => '',
                    ...(match ($type->getName()) {
                        'string' => ['type' => 'string'],
                        'float' => [
                            'type' => 'number',
                            'format' => 'double',
                        ],
                        'bool' => ['type' => 'boolean'],
                        'array' => ['type' => 'array', 'items' => $this->emptySchema()],
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

                if ($this->isUnionSubclass($className)) {
                    return $this->unionResourceSchema($className);
                }

                return [
                    '$ref' => static::componentPath(static::resourceRefName($className)),
                ];
            }
        }

        return $this->emptySchema();
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

    protected function unionResourceSchema(string $className): array|stdClass
    {
        $oneOf = $this->createOneOfArray($className);

        return $oneOf === []
            ? $this->emptySchema()
            : ['oneOf' => $oneOf];
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

    protected function pruneUnusedComponents(): void
    {
        if (!isset($this->document['components']['parameters'])) {
            return;
        }

        $referenced = $this->collectReferencedComponents($this->document);

        foreach (array_keys($this->document['components']['parameters']) as $name) {
            $key = "#/components/parameters/{$name}";
            if (!isset($referenced[$key])) {
                unset($this->document['components']['parameters'][$name]);
            }
        }

        if ($this->document['components']['parameters'] === []) {
            unset($this->document['components']['parameters']);
        }

        if ($this->document['components'] === []) {
            unset($this->document['components']);
        }
    }

    protected function collectReferencedComponents(mixed $node, array &$refs = []): array
    {
        if (is_array($node)) {
            foreach ($node as $key => $value) {
                if ($key === '$ref' && is_string($value)) {
                    $refs[$value] = true;
                } else {
                    $this->collectReferencedComponents($value, $refs);
                }
            }
        }

        return $refs;
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
