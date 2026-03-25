<?php

namespace Seier\Resting\Tests\Fields;

use Seier\Resting\Tests\TestCase;
use Seier\Resting\Tests\Meta\Person;
use Seier\Resting\Fields\ResourceField;
use Seier\Resting\Tests\Meta\PetResource;
use Seier\Resting\Tests\Meta\PersonResource;
use Seier\Resting\Exceptions\ValidationException;
use Seier\Resting\Exceptions\RestingDefinitionException;
use Seier\Resting\Tests\Meta\RequiredConstructorParamsResource;

class ResourceFieldTest extends TestCase
{


    private ResourceField $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new ResourceField(fn () => new PersonResource);
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

    public function testSetWhenGivenArrayWithNullValuesCanOverride()
    {
        $this->instance = new ResourceField(fn () => PersonResource::nullableName());

        $this->instance->set(['name' => 'A', 'age' => 1]);
        $this->assertType($this->instance->get(), function (PersonResource $resource) {
            $this->assertEquals('A', $resource->name->get());
        });

        $this->instance->set(['name' => null, 'age' => 1]);
        $this->assertType($this->instance->get(), function (PersonResource $resource) {
            $this->assertNull($resource->name->get());
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

    public function testApplyCanSetValue()
    {
        $name = $this->faker->name;
        $age = $this->faker->randomNumber(2);

        $this->assertNull($this->instance->get());
        $givenPersonResource = null;

        $this->instance->apply(
            function (PersonResource $resource) use ($age, $name, &$givenPersonResource) {
                $resource->name->set($name);
                $resource->age->set($age);
                $givenPersonResource = $resource;
            }
        );

        $value = $this->instance->get();
        $this->assertSame($givenPersonResource, $value);
        $this->assertInstanceOf(PersonResource::class, $value);
        $this->assertSame($name, $value->name->get());
        $this->assertSame($age, $value->age->get());
    }

    public function testApplyCanSetValueUsingFactoryMethod()
    {
        $name = $this->faker->name;
        $age = $this->faker->randomNumber(2);
        $person = Person::from($name, $age);

        $this->assertNull($this->instance->get());

        $this->instance->apply(
            fn (PersonResource $resource) => $resource->from($person)
        );

        $value = $this->instance->get();
        $this->assertInstanceOf(PersonResource::class, $value);
        $this->assertSame($name, $value->name->get());
        $this->assertSame($age, $value->age->get());
    }

    public function testApplyNullable()
    {
        $name = $this->faker->name;
        $age = $this->faker->randomNumber(2);
        $person = Person::from($name, $age);

        $this->assertNull($this->instance->get());
        $this->instance->applyNullable(
            $person,
            fn (PersonResource $resource) => $resource->from($person)
        );

        $value = $this->instance->get();
        $this->assertInstanceOf(PersonResource::class, $value);
        $this->assertSame($name, $value->name->get());
        $this->assertSame($age, $value->age->get());

        $this->instance->nullable();
        $this->instance->applyNullable(
            null,
            fn (PersonResource $resource) => $resource->from(null) // Expected not to be called
        );

        $this->assertSame(null, $this->instance->get());
        $this->assertTrue($this->instance->isNull());
    }

    public function testConstructorAcceptsClassName()
    {
        $field = new ResourceField(PersonResource::class);

        $this->assertInstanceOf(PersonResource::class, $field->getResourcePrototype());
    }

    public function testConstructorWithClassNameCanSetArray()
    {
        $field = new ResourceField(PersonResource::class);

        $field->set([
            'name' => $name = $this->faker->name,
            'age' => $age = $this->faker->randomNumber(2),
        ]);

        $this->assertType($field->get(), function (PersonResource $resource) use ($name, $age) {
            $this->assertEquals($name, $resource->name->get());
            $this->assertEquals($age, $resource->age->get());
        });
    }

    public function testConstructorWithClassNameCanSetResource()
    {
        $field = new ResourceField(PersonResource::class);

        $person = new PersonResource();
        $field->set($person);

        $this->assertSame($person, $field->get());
    }

    public function testConstructorWithClassNameRejectsNonResourceClass()
    {
        $this->assertThrows(RestingDefinitionException::class, function () {
            new ResourceField(Person::class);
        });
    }

    public function testConstructorWithClassNameRejectsRequiredConstructorParams()
    {
        $this->assertThrows(RestingDefinitionException::class, function () {
            new ResourceField(RequiredConstructorParamsResource::class);
        });
    }

    public function testApplyNullableClosureIsGivenValue()
    {
        $name = $this->faker->name;
        $age = $this->faker->randomNumber(2);
        $person = Person::from($name, $age);

        $givenValue = null;
        $this->assertNull($this->instance->get());
        $this->instance->applyNullable(
            $person,
            function (PersonResource $resource, Person $value) use ($person, &$givenValue) {
                $givenValue = $value;
                return $resource->from($person);
            }
        );

        $value = $this->instance->get();
        $this->assertInstanceOf(PersonResource::class, $value);
        $this->assertSame($name, $value->name->get());
        $this->assertSame($age, $value->age->get());

        $this->assertSame($person, $givenValue);
    }
}
