<?php

namespace Seier\Resting\Tests\Fields;

use Seier\Resting\Tests\TestCase;
use Jchook\AssertThrows\AssertThrows;
use Seier\Resting\Fields\ResourceField;
use Seier\Resting\Tests\Meta\PetResource;
use Seier\Resting\Tests\Meta\PersonResource;
use Seier\Resting\Exceptions\ValidationException;

class ResourceFieldTest extends TestCase
{

    use AssertThrows;

    private ResourceField $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new ResourceField(fn() => new PersonResource);
    }

    public function testGetEmptyReturnsNull()
    {
        $this->assertNull($this->instance->get());
    }

    public function testIsNullReturnsTrue()
    {
        $this->assertTrue($this->instance->isNull());
    }

    public function testSetWhenGivenArray()
    {
        $this->instance->set([
            'name' => $name = $this->faker->name,
            'age' => $age = $this->faker->randomNumber(2),
        ]);

        $this->assertType($this->instance->get(), function (PersonResource $resource) use ($name, $age) {
            $this->assertEquals($name, $resource->name->get());
            $this->assertEquals($age, $resource->age->get());
        });
    }

    public function testSetValidationWhenGivenArray()
    {
        $assertion = function (ValidationException $exception) {
            $this->assertCount(2, $exception->getErrors());
        };

        $this->assertThrows(ValidationException::class, function () {
            $this->instance->set(['name' => null]);
        }, $assertion);
    }

    public function testSetValidationWhenGivenMultipleInvalidValues()
    {
        $assertion = function (ValidationException $exception) {
            $this->assertCount(2, $exception->getErrors());
        };

        $this->assertThrows(ValidationException::class, function () {
            $this->instance->set(['name' => null, 'age' => null]);
        }, $assertion);
    }

    public function testSetWhenGivenCorrectResource()
    {
        $this->instance->set($expect = new PersonResource);

        $this->assertSame($expect, $this->instance->get());
    }

    public function testSetWhenGivenIncorrectResource()
    {
        $assertion = function (ValidationException $exception) {
            $this->assertCount(1, $exception->getErrors());
        };

        $this->assertThrows(ValidationException::class, function () {
            $this->instance->set(new PetResource());
        }, $assertion);
    }

    public function testNullableSetWhenGivenNull()
    {
        $this->instance->nullable();

        $this->instance->set(null);
        $this->assertNull($this->instance->get());
    }

    public function testNonNullableSetWhenGivenNull()
    {
        $this->instance->nullable(false);

        $this->assertThrows(ValidationException::class, function () {
            $this->instance->set(null);
        });
    }
}
