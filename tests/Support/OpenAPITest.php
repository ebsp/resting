<?php


namespace Seier\Resting\Tests\Support;


use stdClass;
use Illuminate\Routing\Route;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\Support\OpenAPI;
use Illuminate\Routing\RouteCollection;
use Seier\Resting\Tests\Meta\PersonParams;
use Seier\Resting\Tests\Meta\PetResource;
use Seier\Resting\Tests\Meta\UnionResourceA;
use Seier\Resting\Tests\Meta\UnionResourceB;
use Seier\Resting\Tests\Meta\PersonResource;
use Seier\Resting\Tests\Meta\AnnotatedRoutes;
use Seier\Resting\Tests\Meta\EmptyUnionResource;
use Seier\Resting\Tests\Meta\RawFieldResource;
use Seier\Resting\Tests\Meta\UnionResourceBase;
use Seier\Resting\Tests\Meta\UnionParentResource;
use Seier\Resting\Tests\Meta\ArrayFieldsResource;
use Seier\Resting\Tests\Meta\UnionListParentResource;
use Seier\Resting\Tests\Meta\ArrayResourceFieldsResource;

class OpenAPITest extends TestCase
{
    public function testConstructor()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['GET'], '/url', function () {
            return 'test';
        }));

        new OpenAPI($routeCollection);

        $this->assertTrue(true);
    }

    public function testInputUnionResourceCompositionHasSchemaResources()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add((new Route(['POST'], 'input/union/composition', fn (UnionParentResource $r) => null)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertComponentExists($schema, UnionParentResource::class);
        $this->assertComponentExists($schema, UnionResourceA::class);
        $this->assertComponentExists($schema, UnionResourceB::class);
    }

    public function testInputUnionResourceInheritance()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add((new Route(['POST'], 'input/union/inheritance', fn (UnionResourceBase $r) => null)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertComponentNotExists($schema, UnionResourceBase::class);
        $this->assertComponentExists($schema, UnionResourceA::class);
        $this->assertComponentExists($schema, UnionResourceB::class);
    }

    public function testInputUnionResourceVariadic()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add((new Route(['POST'], 'input/union/variadic', fn (UnionResourceBase ...$r) => null)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertComponentNotExists($schema, UnionResourceBase::class);
        $this->assertComponentExists($schema, UnionResourceA::class);
        $this->assertComponentExists($schema, UnionResourceB::class);
    }

    public function testOutputUnionResourceHasLiteralDiscriminatorKey()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add((new Route(['POST'], 'input/union/variadic', fn (UnionResourceBase ...$r) => null)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $unionResourceA = $this->assertComponentExists($schema, UnionResourceA::class);
        $unionResourceB = $this->assertComponentExists($schema, UnionResourceB::class);

        $unionResourceADiscriminator = $this->assertPropertyExists($unionResourceA, 'discriminator');
        $this->assertPropertyHasEnumConstraint($unionResourceADiscriminator, ['a']);

        $unionResourceBDiscriminator = $this->assertPropertyExists($unionResourceB, 'discriminator');
        $this->assertPropertyHasEnumConstraint($unionResourceBDiscriminator, ['b']);
    }

    public function testOutputUnionResourceComposition()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add((new Route(['POST'], 'output/union/variadic', fn (): UnionParentResource => null)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertComponentExists($schema, UnionParentResource::class);
        $this->assertComponentExists($schema, UnionResourceA::class);
        $this->assertComponentExists($schema, UnionResourceB::class);
    }

    public function testOutputUnionResourceInheritance()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add((new Route(['POST'], 'output/union/inheritance', fn (): UnionResourceBase => null)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertComponentNotExists($schema, UnionResourceBase::class);
        $this->assertComponentExists($schema, UnionResourceA::class);
        $this->assertComponentExists($schema, UnionResourceB::class);
    }

    public function testOutputUnionResourceListInheritance()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['POST'], 'output/union/inheritance', AnnotatedRoutes::listsUnionBase(...)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertComponentNotExists($schema, UnionResourceBase::class);
        $this->assertComponentExists($schema, UnionResourceA::class);
        $this->assertComponentExists($schema, UnionResourceB::class);
    }

    public function testOutputUnionResourceListInheritanceCombination()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['POST'], 'output/union/inheritance', AnnotatedRoutes::listsUnionCombination(...)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertComponentNotExists($schema, UnionResourceBase::class);
        $this->assertComponentExists($schema, UnionParentResource::class);
        $this->assertComponentExists($schema, UnionResourceA::class);
        $this->assertComponentExists($schema, UnionResourceB::class);
    }

    public function testOutputSupportsArrayFieldElementValidation()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add((new Route(['POST'], 'x', fn (ArrayFieldsResource $r) => null)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $component = $this->assertComponentExists($schema, ArrayFieldsResource::class);

        $this->assertArraySubset(
            [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                    'nullable' => false,
                ]
            ],
            $component['properties']['with_strings']
        );

        $this->assertArraySubset(
            [
                'type' => 'array',
                'items' => [
                    'type' => 'integer',
                    'nullable' => false,
                ]
            ],
            $component['properties']['with_integers']
        );

        $this->assertArraySubset(
            [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                    'nullable' => false,
                    'enum' => [
                        'hearts',
                        'diamonds',
                        'clubs',
                        'spades',
                    ]
                ]
            ],
            $component['properties']['with_enums']
        );

        $this->assertArraySubset(
            [
                'type' => 'array',
                'items' => [
                    'type' => 'boolean',
                    'nullable' => false,
                ]
            ],
            $component['properties']['with_booleans']
        );

        $this->assertArraySubset(
            [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                    'nullable' => true,
                ]
            ],
            $component['properties']['with_nullable_strings']
        );

        $this->assertArraySubset(
            [
                'type' => 'array',
                'items' => [
                    'type' => 'integer',
                    'nullable' => true,
                ]
            ],
            $component['properties']['with_nullable_integers']
        );

        $this->assertArraySubset(
            [
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                    'nullable' => true,
                    'enum' => [
                        'hearts',
                        'diamonds',
                        'clubs',
                        'spades',
                    ]
                ]
            ],
            $component['properties']['with_nullable_enums']
        );

        $this->assertArraySubset(
            [
                'type' => 'array',
                'items' => [
                    'type' => 'boolean',
                    'nullable' => true,
                ]
            ],
            $component['properties']['with_nullable_booleans']
        );
    }

    public function testOutputSupportsResourceArrayFieldElementValidation()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add((new Route(['POST'], 'x', fn (ArrayResourceFieldsResource $r) => null)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $component = $this->assertComponentExists($schema, ArrayResourceFieldsResource::class);

        $this->assertArraySubset(
            [
                'type' => 'array',
                'items' => [
                    '$ref' => OpenAPI::componentPath(OpenAPI::resourceRefName(PersonResource::class)),
                ]
            ],
            $component['properties']['persons']
        );

        $this->assertArrayNotHasKey('type', $component['properties']['persons']['items']);
        $this->assertArrayNotHasKey('nullable', $component['properties']['persons']['items']);

        $this->assertArraySubset(
            [
                'type' => 'array',
                'items' => [
                    'nullable' => true,
                    'allOf' => [
                        ['$ref' => OpenAPI::componentPath(OpenAPI::resourceRefName(PersonResource::class))],
                    ],
                ]
            ],
            $component['properties']['nullable_persons']
        );
    }

    public function testOutputSupportsScalarReturnValues()
    {
        $routeCollection = new RouteCollection();

        $routeCollection->add((new Route(['GET'], 'a', fn (): array => null)));
        $routeCollection->add((new Route(['GET'], 'b', fn (): bool => null)));
        $routeCollection->add((new Route(['GET'], 'c', fn (): int => null)));
        $routeCollection->add((new Route(['GET'], 'd', fn (): string => null)));
        $routeCollection->add((new Route(['GET'], 'e', fn (): float => null)));
        $routeCollection->add((new Route(['GET'], 'f', fn (): ?array => null)));
        $routeCollection->add((new Route(['GET'], 'g', fn (): ?bool => null)));
        $routeCollection->add((new Route(['GET'], 'h', fn (): ?int => null)));
        $routeCollection->add((new Route(['GET'], 'i', fn (): ?string => null)));
        $routeCollection->add((new Route(['GET'], 'j', fn (): ?float => null)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $schemaA = $schema['paths']['/a']['get']['responses']['200']['content']['application/json']['schema'];
        $this->assertArraySubset(['type' => 'array', 'nullable' => false], $schemaA);
        $this->assertInstanceOf(stdClass::class, $schemaA['items']);

        $this->assertArraySubset(
            ['type' => 'boolean', 'nullable' => false],
            $schema['paths']['/b']['get']['responses']['200']['content']['application/json']['schema']
        );

        $this->assertArraySubset(
            ['type' => 'integer', 'nullable' => false, 'format' => 'int64'],
            $schema['paths']['/c']['get']['responses']['200']['content']['application/json']['schema']
        );

        $this->assertArraySubset(
            ['type' => 'string', 'nullable' => false],
            $schema['paths']['/d']['get']['responses']['200']['content']['application/json']['schema']
        );

        $this->assertArraySubset(
            ['type' => 'number', 'nullable' => false, 'format' => 'double'],
            $schema['paths']['/e']['get']['responses']['200']['content']['application/json']['schema']
        );

        $schemaF = $schema['paths']['/f']['get']['responses']['200']['content']['application/json']['schema'];
        $this->assertArraySubset(['type' => 'array', 'nullable' => true], $schemaF);
        $this->assertInstanceOf(stdClass::class, $schemaF['items']);

        $this->assertArraySubset(
            ['type' => 'boolean', 'nullable' => true],
            $schema['paths']['/g']['get']['responses']['200']['content']['application/json']['schema']
        );

        $this->assertArraySubset(
            ['type' => 'integer', 'nullable' => true, 'format' => 'int64'],
            $schema['paths']['/h']['get']['responses']['200']['content']['application/json']['schema']
        );

        $this->assertArraySubset(
            ['type' => 'string', 'nullable' => true],
            $schema['paths']['/i']['get']['responses']['200']['content']['application/json']['schema']
        );

        $this->assertArraySubset(
            ['type' => 'number', 'nullable' => true, 'format' => 'double'],
            $schema['paths']['/j']['get']['responses']['200']['content']['application/json']['schema']
        );
    }

    public function testOutputSupportsResourceUnionResponseTypes()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add((new Route(['GET'], 'a', fn (): PersonResource|PetResource => null)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertArraySubset(
            [
                'nullable' => false,
                'oneOf' => [
                    ['$ref' => OpenAPI::componentPath(OpenAPI::resourceRefName(PersonResource::class))],
                    ['$ref' => OpenAPI::componentPath(OpenAPI::resourceRefName(PetResource::class))],
                ]
            ],
            $schema['paths']['/a']['get']['responses']['200']['content']['application/json']['schema']
        );
    }

    public function testOutputSupportsNullableResourceUnionResponseTypes()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add((new Route(['GET'], 'a', fn (): PersonResource|PetResource|null => null)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertArraySubset(
            [
                'nullable' => true,
                'oneOf' => [
                    ['$ref' => OpenAPI::componentPath(OpenAPI::resourceRefName(PersonResource::class))],
                    ['$ref' => OpenAPI::componentPath(OpenAPI::resourceRefName(PetResource::class))],
                ]
            ],
            $schema['paths']['/a']['get']['responses']['200']['content']['application/json']['schema']
        );
    }

    public function testOutputSupportsResourceUnionScalarResponseTypes()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add((new Route(['GET'], 'a', fn (): int|string => null)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertArraySubset(
            [
                'nullable' => false,
                'oneOf' => [
                    ['type' => 'string'],
                    ['type' => 'integer'],
                ]
            ],
            $schema['paths']['/a']['get']['responses']['200']['content']['application/json']['schema']
        );
    }

    public function testOutputSupportsNullableResourceUnionScalarResponseTypes()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add((new Route(['GET'], 'a', fn (): int|string|null => null)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertArraySubset(
            [
                'nullable' => true,
                'oneOf' => [
                    ['type' => 'string'],
                    ['type' => 'integer'],
                ]
            ],
            $schema['paths']['/a']['get']['responses']['200']['content']['application/json']['schema']
        );
    }

    public function testInputOutputUnionListParentResource()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['POST'], 'output/union/list', fn (UnionListParentResource $resource) => null));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertComponentNotExists($schema, UnionResourceBase::class);
        $this->assertComponentExists($schema, UnionListParentResource::class);
        $this->assertComponentExists($schema, UnionResourceA::class);
        $this->assertComponentExists($schema, UnionResourceB::class);
    }

    public function testInputHandleScalarParameters()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['POST'], 'scalar_parameters', fn (string $string) => null));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertArrayHasKey('/scalar_parameters', $schema['paths']);
        $operation = $schema['paths']['/scalar_parameters']['post'];

        $this->assertSame([], $operation['parameters'] ?? []);
        $this->assertArrayNotHasKey('requestBody', $operation);
    }

    public function testInputScalarUriParameterEmittedAsPathParameter()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['GET'], 'items/{id}', fn (int $id) => null));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $parameters = $schema['paths']['/items/{id}']['get']['parameters'] ?? [];

        $matched = null;
        foreach ($parameters as $parameter) {
            if (($parameter['name'] ?? null) === 'id' && ($parameter['in'] ?? null) === 'path') {
                $matched = $parameter;
                break;
            }
        }

        $this->assertNotNull($matched, 'Expected a path parameter named "id"');
        $this->assertTrue($matched['required']);
        $this->assertSame('integer', $matched['schema']['type']);
        $this->assertArrayNotHasKey('description', $matched);
    }

    public function testInputDocOnRequestBodyParameterDescribesRequestBody()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['POST'], 'persons', AnnotatedRoutes::annotatedRequestBody(...)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $requestBody = $schema['paths']['/persons']['post']['requestBody'];

        $this->assertSame(AnnotatedRoutes::REQUEST_BODY_PARAM_DOC, $requestBody['description']);
        $this->assertTrue($requestBody['required']);
    }

    public function testInputDocOnScalarUriParameterDescribesPathParameter()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['GET'], 'users/{id}', AnnotatedRoutes::pathParamEndpoint(...)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $parameters = $schema['paths']['/users/{id}']['get']['parameters'] ?? [];

        $matched = null;
        foreach ($parameters as $parameter) {
            if (($parameter['name'] ?? null) === 'id' && ($parameter['in'] ?? null) === 'path') {
                $matched = $parameter;
                break;
            }
        }

        $this->assertNotNull($matched, 'Expected a path parameter named "id"');
        $this->assertSame(AnnotatedRoutes::PATH_PARAM_DOC, $matched['description']);
        $this->assertSame('integer', $matched['schema']['type']);
        $this->assertTrue($matched['required']);
    }

    public function testResponseSchemaIsObjectWhenNoReturnType()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['GET'], 'no-return-type', fn () => null));

        $openAPI = new OpenAPI($routeCollection);
        $json = json_encode($openAPI->toArray());

        $this->assertStringContainsString('"schema":{}', $json);
        $this->assertStringNotContainsString('"schema":[]', $json);
    }

    public function testResponseObjectAlwaysHasDescription()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['GET'], 'a', fn () => null));
        $routeCollection->add(new Route(['POST'], 'b', fn (PersonResource $r) => null));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        foreach ($schema['paths'] as $path => $methods) {
            foreach ($methods as $method => $operation) {
                foreach ($operation['responses'] as $code => $response) {
                    $this->assertArrayHasKey(
                        'description',
                        $response,
                        "Response $code on $method $path is missing 'description'."
                    );
                    $this->assertNotSame('', $response['description']);
                }
            }
        }
    }

    public function testResourceReturnTypeProducesRefWithoutSiblings()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['GET'], 'person', fn (): PersonResource => null));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $responseSchema = $schema['paths']['/person']['get']['responses']['200']['content']['application/json']['schema'];

        $this->assertSame(
            ['$ref' => OpenAPI::componentPath(OpenAPI::resourceRefName(PersonResource::class))],
            $responseSchema
        );
        $this->assertArrayNotHasKey('type', $responseSchema);
        $this->assertArrayNotHasKey('nullable', $responseSchema);
    }

    public function testItemsIsObjectForPlainArrayReturnType()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['GET'], 'list', fn (): array => null));

        $openAPI = new OpenAPI($routeCollection);
        $document = $openAPI->toArray();

        $schema = $document['paths']['/list']['get']['responses']['200']['content']['application/json']['schema'];

        $this->assertSame('array', $schema['type']);
        $this->assertInstanceOf(stdClass::class, $schema['items']);

        $json = json_encode($document);
        $this->assertStringContainsString('"items":{}', $json);
    }

    public function testOneOfNeverHasEmptyMembers()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['GET'], 'a', fn (): PersonResource|PetResource => null));
        $routeCollection->add(new Route(['GET'], 'b', fn (): PersonResource|PetResource|null => null));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertNoEmptyOneOf($schema);
    }

    public function testUnionResourceWithoutDependentsDoesNotEmitEmptyOneOf()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['GET'], 'response-empty-union', fn (): EmptyUnionResource => null));
        $routeCollection->add(new Route(['POST'], 'request-empty-union', fn (EmptyUnionResource $r) => null));
        $routeCollection->add(new Route(['POST'], 'variadic-empty-union', fn (EmptyUnionResource ...$r) => null));

        $openAPI = new OpenAPI($routeCollection);
        $document = $openAPI->toArray();

        $this->assertNoEmptyOneOf($document);

        $responseSchema = $document['paths']['/response-empty-union']['get']['responses']['200']['content']['application/json']['schema'];
        $this->assertInstanceOf(stdClass::class, $responseSchema);

        $requestSchema = $document['paths']['/request-empty-union']['post']['requestBody']['content']['application/json']['schema'];
        $this->assertInstanceOf(stdClass::class, $requestSchema);

        $variadicSchema = $document['paths']['/variadic-empty-union']['post']['requestBody']['content']['application/json']['schema'];
        $this->assertSame('array', $variadicSchema['type']);
        $this->assertInstanceOf(stdClass::class, $variadicSchema['items']);

        $json = json_encode($document, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('"oneOf":[]', $json);
    }

    public function testTypeAnyNeverAppears()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['POST'], 'raw', fn (RawFieldResource $r) => null));

        $openAPI = new OpenAPI($routeCollection);
        $json = json_encode($openAPI->toArray());

        $this->assertStringNotContainsString('"type":"any"', $json);
    }

    public function testRefIsNeverEmittedWithSiblings()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['POST'], 'union', fn (UnionResourceBase $r) => null));
        $routeCollection->add(new Route(['GET'], 'person', fn (): PersonResource => null));
        $routeCollection->add(new Route(['POST'], 'persons', fn (UnionParentResource $r) => null));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertNoRefSiblings($schema);
    }

    public function testUnmatchedUriPlaceholderEmitsPathParameter()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['DELETE'], 'items/{itemId}', fn () => null));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $parameters = $schema['paths']['/items/{itemId}']['delete']['parameters'] ?? [];

        $matched = null;
        foreach ($parameters as $p) {
            if (($p['name'] ?? null) === 'itemId') {
                $matched = $p;
                break;
            }
        }

        $this->assertNotNull($matched, 'Expected a path parameter for "itemId".');
        $this->assertSame('path', $matched['in']);
        $this->assertTrue($matched['required']);
        $this->assertSame('string', $matched['schema']['type']);
    }

    public function testParamsResourcePlaceholderIsNotDuplicated()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['GET'], 'persons/{id}', fn (PersonParams $p) => null));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $parameters = $schema['paths']['/persons/{id}']['get']['parameters'] ?? [];

        $idCount = 0;
        foreach ($parameters as $p) {
            if (isset($p['$ref']) && str_ends_with($p['$ref'], '_id')) {
                $idCount++;
            }
            if (($p['name'] ?? null) === 'id') {
                $idCount++;
            }
        }

        $this->assertSame(1, $idCount, 'Expected exactly one parameter entry for "id".');
    }

    public function testUnusedParameterComponentsArePruned()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['GET'], 'a', fn () => null));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertArrayNotHasKey('parameters', $schema['components'] ?? []);
    }

    public function testDiscriminatorEnumIsNeverNullForIntermediateUnion()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['POST'], 'union', fn (UnionResourceBase $r) => null));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        foreach ($schema['components']['schemas'] ?? [] as $name => $componentSchema) {
            foreach ($componentSchema['properties'] ?? [] as $propName => $prop) {
                if (isset($prop['enum'])) {
                    foreach ($prop['enum'] as $value) {
                        $this->assertNotNull(
                            $value,
                            "enum on $name.$propName contains null."
                        );
                    }
                }
            }
        }
    }

    public function testGeneratedDocumentIsValidJson()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['GET'], 'a', fn () => null));
        $routeCollection->add(new Route(['POST'], 'b', fn (PersonResource $r) => null));
        $routeCollection->add(new Route(['POST'], 'union', fn (UnionResourceBase $r) => null));
        $routeCollection->add(new Route(['POST'], 'union-parent', fn (UnionParentResource $r) => null));
        $routeCollection->add(new Route(['GET'], 'list', fn (): array => null));
        $routeCollection->add(new Route(['DELETE'], 'items/{itemId}', fn () => null));

        $openAPI = new OpenAPI($routeCollection);
        $json = json_encode($openAPI->toArray(), JSON_THROW_ON_ERROR);

        $this->assertIsString($json);
        $this->assertStringNotContainsString('"schema":[]', $json);
        $this->assertStringNotContainsString('"items":[]', $json);
        $this->assertStringNotContainsString('"type":"any"', $json);
    }

    private function assertNoRefSiblings(mixed $node, string $path = '$'): void
    {
        if (is_array($node)) {
            if (isset($node['$ref'])) {
                $siblings = array_diff(array_keys($node), ['$ref']);
                $this->assertSame(
                    [],
                    $siblings,
                    "\$ref at $path has forbidden sibling keys: " . implode(', ', $siblings)
                );
            }
            foreach ($node as $key => $child) {
                $this->assertNoRefSiblings($child, $path . '.' . $key);
            }
        }
    }

    private function assertNoEmptyOneOf(mixed $node, string $path = '$'): void
    {
        if (is_array($node)) {
            foreach ($node as $key => $value) {
                if ($key === 'oneOf') {
                    $this->assertIsArray($value, "oneOf at $path is not an array.");
                    $this->assertNotSame([], $value, "oneOf at $path is empty.");
                    foreach ($value as $i => $member) {
                        $this->assertTrue(
                            (is_array($member) && $member !== []) || $member instanceof stdClass,
                            "oneOf at $path index $i is an empty schema."
                        );
                    }
                }
                $this->assertNoEmptyOneOf($value, $path . '.' . $key);
            }
        }
    }

    private function assertComponentExists(array $schema, string $resource): array
    {
        $refName = static::resourceRefName($resource);

        $this->assertArrayHasKey('components', $schema);
        $this->assertArrayHasKey('schemas', $schema['components']);
        $this->assertArrayHasKey($refName, $schema['components']['schemas']);

        return $schema['components']['schemas'][$refName];
    }

    private function assertComponentNotExists(array $schema, string $resource): void
    {
        $this->assertArrayHasKey('components', $schema);
        $this->assertArrayHasKey('schemas', $schema['components']);
        $this->assertArrayNotHasKey(static::resourceRefName($resource), $schema['components']['schemas']);
    }

    public static function resourceRefName($resourceClass): array|string
    {
        return str_replace(['App\\Api\\Resources\\', '\\'], ['', '_'], $resourceClass);
    }

    private function assertPropertyExists(array $resource, string $propertyKey)
    {
        $this->assertArrayHasKey('properties', $resource, 'Component does not have any properties');
        $this->assertArrayHasKey($propertyKey, $resource['properties']);

        return $resource['properties'][$propertyKey];
    }

    private function assertPropertyHasEnumConstraint(array $property, array $enumValues): void
    {
        $this->assertArrayHasKey('enum', $property, 'Property does not have enum constraint');
        $this->assertEquals($enumValues, $property['enum'], 'Property enum constraints do not match expected constraints.');
    }
}