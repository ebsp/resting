<?php


namespace Seier\Resting\Tests\Support;


use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Seier\Resting\Support\OpenAPI;
use Seier\Resting\Tests\Resources\ExtendsUnionResource;
use Seier\Resting\Tests\Resources\UnionParentResource;
use Seier\Resting\Tests\Resources\UnionResourceA;
use Seier\Resting\Tests\Resources\UnionResourceB;
use Seier\Resting\Tests\TestCase;

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
        $routeCollection->add((new Route(['POST'], 'composition', fn(UnionParentResource $r) => null)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertComponentExists($schema, UnionParentResource::class);
        $this->assertComponentExists($schema, UnionResourceA::class);
        $this->assertComponentExists($schema, UnionResourceB::class);
    }

    public function testInputUnionResourceInheritance()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add((new Route(['POST'], 'inheritance', fn(ExtendsUnionResource $r) => null)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertComponentNotExists($schema, ExtendsUnionResource::class);
        $this->assertComponentExists($schema, UnionResourceA::class);
        $this->assertComponentExists($schema, UnionResourceB::class);
    }

    public function testInputUnionResourceVariadic()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add((new Route(['POST'], 'variadic', fn(ExtendsUnionResource ...$r) => null)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertComponentNotExists($schema, ExtendsUnionResource::class);
        $this->assertComponentExists($schema, UnionResourceA::class);
        $this->assertComponentExists($schema, UnionResourceB::class);
    }

    public function testAids(){
        $routeCollection = new RouteCollection();
        $routeCollection->add((new Route(['POST'], 'composition', fn(UnionParentResource $r) => null)));
        $routeCollection->add((new Route(['POST'], 'inheritance', fn(ExtendsUnionResource $r) => null)));
        $routeCollection->add((new Route(['POST'], 'variadic', fn(ExtendsUnionResource ...$r) => null)));
        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();
        var_dump(json_encode($schema));
    }

    private function assertComponentExists(array $schema, string $resource)
    {
        $this->assertArrayHasKey('components', $schema);
        $this->assertArrayHasKey('schemas', $schema['components']);
        $this->assertArrayHasKey(static::resourceRefName($resource), $schema['components']['schemas']);
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
}