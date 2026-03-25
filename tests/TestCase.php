<?php

namespace Seier\Resting\Tests;

use Closure;
use Faker\Factory;
use ReflectionFunction;
use Faker\Generator as Faker;
use Seier\Resting\ResourceFactory;
use Seier\Resting\RestingSettings;
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
        RestingSettings::reset();
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

    public function assertThrows(
        Closure|string $test,
        Closure|string $expectedClass = 'Throwable',
        Closure|string|null $expectedMessage = null,
    ): void {
        // Support old pattern: assertThrows(ExceptionClass, callback, ?assertionClosure)
        if (is_string($test) && $expectedClass instanceof Closure) {
            $exceptionClass = $test;
            $callback = $expectedClass;
            $assertion = $expectedMessage instanceof Closure ? $expectedMessage : null;

            try {
                $callback();
                $this->fail("Expected exception {$exceptionClass} was not thrown");
            } catch (\Throwable $e) {
                $this->assertInstanceOf($exceptionClass, $e);
                if ($assertion) {
                    $assertion($e);
                }
            }

            return;
        }

        parent::assertThrows($test, $expectedClass, $expectedMessage);
    }

    protected static function assertArraySubset(array $subset, array $array, bool $strict = false, string $message = ''): void
    {
        foreach ($subset as $key => $value) {
            static::assertArrayHasKey($key, $array, $message);
            if (is_array($value) && is_array($array[$key])) {
                static::assertArraySubset($value, $array[$key], $strict, $message);
            } elseif ($strict) {
                static::assertSame($value, $array[$key], $message);
            } else {
                static::assertEquals($value, $array[$key], $message);
            }
        }
    }

    protected function getPackageProviders($app): array
    {
        return [
            RestingServiceProvider::class,
        ];
    }
}
