<?php

namespace Seier\Resting\Tests\Support;

use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Seier\Resting\Support\OpenAPI;
use Seier\Resting\Tests\Meta\PersonResource;
use Seier\Resting\Tests\Meta\PersonParams;
use Seier\Resting\Tests\Meta\PetResource;
use Seier\Resting\Tests\Meta\RawFieldResource;
use Seier\Resting\Tests\Meta\UnionParentResource;
use Seier\Resting\Tests\Meta\UnionResourceA;
use Seier\Resting\Tests\Meta\UnionResourceB;
use Seier\Resting\Tests\Meta\UnionResourceBase;
use Seier\Resting\Tests\TestCase;

class OpenAPIValidationFixesTest extends TestCase
{
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
        $this->assertInstanceOf(\stdClass::class, $schema['items']);

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
                            (is_array($member) && $member !== []) || $member instanceof \stdClass,
                            "oneOf at $path index $i is an empty schema."
                        );
                    }
                }
                $this->assertNoEmptyOneOf($value, $path . '.' . $key);
            }
        }
    }
}
