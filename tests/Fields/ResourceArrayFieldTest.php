<?php

namespace Seier\Resting\Tests\Fields;

use Seier\Resting\Tests\TestCase;
use Jchook\AssertThrows\AssertThrows;
use Seier\Resting\Tests\Meta\PetResource;
use Seier\Resting\Fields\ResourceArrayField;
use Seier\Resting\Tests\Meta\PersonResource;
use Seier\Resting\Exceptions\ValidationException;
use Seier\Resting\Tests\Meta\MockSecondaryValidator;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Tests\Meta\MockSecondaryValidationError;
use Seier\Resting\Validation\Errors\NullableValidationError;

class ResourceArrayFieldTest extends TestCase
{

    use AssertsErrors;
    use AssertThrows;

    private ResourceArrayField $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new ResourceArrayField(fn() => new PersonResource);
    }

    public function testGetEmptyReturnsNull()
    {
        $this->assertNull($this->instance->get());
    }

    public function testIsNullReturnsTrue()
    {
        $this->assertTrue($this->instance->isNull());
    }

    public function testSetWhenGivenEmptyArray()
    {
        $this->instance->set([]);

        $this->assertEquals([], $this->instance->get());
    }

    public function testSetWhenGivenArrayOfResources()
    {
        $person = new PersonResource();
        $this->instance->set([$person]);

        $this->assertCount(1, $return = $this->instance->get());
        $this->assertSame($person, $return[0]);
    }

    public function testSetWhenGivenMultidimensionalArray()
    {
        $this->instance->set([[
            'name' => $name = $this->faker->name,
            'age' => $age = $this->faker->randomNumber(2),
        ]]);

        $this->assertCount(1, $return = $this->instance->get());
        $this->assertType($return[0], function (PersonResource $resource) use ($name, $age) {
            $this->assertEquals($name, $resource->name->get());
            $this->assertEquals($age, $resource->age->get());
        });
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

    public function testSetResourceArrayValidation()
    {
        $this->instance->set([
            new PersonResource(),
            new PersonResource(),
        ]);

        $this->assertCount(2, $this->instance);
        $this->assertEquals(2, $this->instance->count());
    }

    public function testSetArrayValidation()
    {
        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set([
                ['name' => null, 'age' => null],
                ['name' => null, 'age' => null],
            ]);
        });

        $this->assertHasError($exception, NullableValidationError::class, '0.name');
        $this->assertHasError($exception, NullableValidationError::class, '0.age');
        $this->assertHasError($exception, NullableValidationError::class, '1.name');
        $this->assertHasError($exception, NullableValidationError::class, '1.age');
    }

    public function testValidateWithRegisteredSecondaryValidationThatPasses()
    {
        $this->instance->withValidator(MockSecondaryValidator::pass());

        $this->instance->set([]);
        $this->assertEquals([], $this->instance->get());
    }

    public function testValidateWithRegisteredSecondaryValidationThatFails()
    {
        $this->instance->withValidator(MockSecondaryValidator::fail());

        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set([]);
        });

        $this->assertHasError($exception, MockSecondaryValidationError::class);
    }
}
