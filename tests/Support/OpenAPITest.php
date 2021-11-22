<?php


namespace Seier\Resting\Tests\Support;


use Illuminate\Routing\Route;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\Support\OpenAPI;
use Illuminate\Routing\RouteCollection;
use Seier\Resting\Tests\Meta\UnionResourceA;
use Seier\Resting\Tests\Meta\UnionResourceB;
use Seier\Resting\Tests\Meta\UnionResourceBase;
use Seier\Resting\Tests\Meta\UnionParentResource;
use Seier\Resting\Tests\Meta\UnionListParentResource;

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
        $routeCollection->add((new Route(['POST'], 'input/union/composition', fn(UnionParentResource $r) => null)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertComponentExists($schema, UnionParentResource::class);
        $this->assertComponentExists($schema, UnionResourceA::class);
        $this->assertComponentExists($schema, UnionResourceB::class);
    }

    public function testInputUnionResourceInheritance()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add((new Route(['POST'], 'input/union/inheritance', fn(UnionResourceBase $r) => null)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertComponentNotExists($schema, UnionResourceBase::class);
        $this->assertComponentExists($schema, UnionResourceA::class);
        $this->assertComponentExists($schema, UnionResourceB::class);
    }

    public function testInputUnionResourceVariadic()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add((new Route(['POST'], 'input/union/variadic', fn(UnionResourceBase ...$r) => null)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertComponentNotExists($schema, UnionResourceBase::class);
        $this->assertComponentExists($schema, UnionResourceA::class);
        $this->assertComponentExists($schema, UnionResourceB::class);
    }

    public function testOutputUnionResourceHasLiteralDiscriminatorKey()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add((new Route(['POST'], 'input/union/variadic', fn(UnionResourceBase ...$r) => null)));

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
        $routeCollection->add((new Route(['POST'], 'output/union/variadic', fn(): UnionParentResource => null)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertComponentExists($schema, UnionParentResource::class);
        $this->assertComponentExists($schema, UnionResourceA::class);
        $this->assertComponentExists($schema, UnionResourceB::class);
    }

    public function testOutputUnionResourceInheritance()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add((new Route(['POST'], 'output/union/inheritance', fn(): UnionResourceBase => null)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertComponentNotExists($schema, UnionResourceBase::class);
        $this->assertComponentExists($schema, UnionResourceA::class);
        $this->assertComponentExists($schema, UnionResourceB::class);
    }

    public function testOutputUnionResourceListInheritance()
    {
        \Illuminate\Routing\Route::macro('lists', function ($resource = null) {
            $this->_lists = $resource;
            return $this;
        });

        $routeCollection = new RouteCollection();
        $routeCollection->add((new Route(['POST'], 'output/union/inheritance', fn() => null))->lists(UnionResourceBase::class));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertComponentNotExists($schema, UnionResourceBase::class);
        $this->assertComponentExists($schema, UnionResourceA::class);
        $this->assertComponentExists($schema, UnionResourceB::class);
    }

    public function testOutputUnionResourceListInheritanceCombination()
    {
        \Illuminate\Routing\Route::macro('lists', function ($resource = null) {
            $this->_lists = $resource;
            return $this;
        });

        $routeCollection = new RouteCollection();
        $routeCollection->add((new Route(['POST'], 'output/union/inheritance', fn(): UnionResourceBase => null))->lists([UnionParentResource::class, UnionResourceBase::class]));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertComponentNotExists($schema, UnionResourceBase::class);
        $this->assertComponentExists($schema, UnionParentResource::class);
        $this->assertComponentExists($schema, UnionResourceA::class);
        $this->assertComponentExists($schema, UnionResourceB::class);
    }

    public function testInputOutputUnionListParentResource()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['POST'], 'output/union/list', fn(UnionListParentResource $resource) => null));

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
        $routeCollection->add(new Route(['POST'], 'scalar_parameters', fn(string $string) => null));

        $openAPI = new OpenAPI($routeCollection);
        $openAPI->toArray();
        $this->assertTrue(true);
    }

    private function assertComponentExists(array $schema, string $resource): array
    {
        $refName = static::resourceRefName($resource);

        $this->assertArrayHasKey('components', $schema);
        $this->assertArrayHasKey('schemas', $schema['components']);
        $this->assertArrayHasKey($refName, $schema['components']['schemas']);

        return $schema['components']['schemas'][$refName];
    }

    private function assertComponentNotExists(array $schema, string $resource)
    {
        $this->assertArrayHasKey('components', $schema);
        $this->assertArrayHasKey('schemas', $schema['components']);
        $this->assertArrayNotHasKey(static::resourceRefName($resource), $schema['components']['schemas']);
    }

    public static function resourceRefName($resourceClass)
    {
        return str_replace(['App\\Api\\Resources\\', '\\'], ['', '_'], $resourceClass);
    }

    private function assertPropertyExists(array $resource, string $propertyKey)
    {
        $this->assertArrayHasKey('properties', $resource, 'Component does not have any properties');
        $this->assertArrayHasKey($propertyKey, $resource['properties']);

        return $resource['properties'][$propertyKey];
    }

    private function assertPropertyHasEnumConstraint(array $property, array $enumValues)
    {
        $this->assertArrayHasKey('enum', $property, 'Property does not have enum constraint');
        $this->assertEquals($enumValues, $property['enum'], 'Property enum constraints do not match expected constraints.');
    }
}