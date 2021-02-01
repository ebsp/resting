<?php

namespace Seier\Resting\Tests;

use Closure;
use Faker\Factory;
use ReflectionFunction;
use Faker\Generator as Faker;
use Seier\Resting\ResourceFactory;
use Seier\Resting\ClosureResourceFactory;
use Seier\Resting\Tests\Meta\PetResource;
use Seier\Resting\Tests\Meta\PersonResource;
use Orchestra\Testbench\TestCase as Orchestra;
use Seier\Resting\Support\Laravel\RestingServiceProvider;

abstract class TestCase extends Orchestra
{

    protected Faker $faker;

    public function setUp(): void
    {
        parent::setUp();

        $this->faker = Factory::create();
    }

    public function resourceFactory(string|Closure $factory): ResourceFactory
    {
        return ClosureResourceFactory::from($factory);
    }

    public function personNullable(): ResourceFactory
    {
        return $this->resourceFactory(function () {
            $person = new PersonResource();
            $person->name->nullable(true);
            $person->age->nullable(true);
            return $person;
        });
    }

    public function petWithNullableOwner(): ResourceFactory
    {
        return $this->resourceFactory(function () {
            $pet = new PetResource();
            $pet->owner->nullable(true);
            return $pet;
        });
    }

    protected function assertType($value, Closure $function)
    {
        $reflection = new ReflectionFunction($function);
        $parameters = $reflection->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertInstanceOf($parameters[0]->getType()->getName(), $value);
        $function($value);
    }

    protected function getPackageProviders($app): array
    {
        return [
            RestingServiceProvider::class,
        ];
    }
}
