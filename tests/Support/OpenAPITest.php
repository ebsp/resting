<?php


namespace Seier\Resting\Tests\Support;


use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Seier\Resting\Support\OpenAPI;
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
}