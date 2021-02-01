<?php

namespace Seier\Resting\Tests\Fields;

use Seier\Resting\Tests\TestCase;
use Seier\Resting\Fields\NumberField;
use Seier\Resting\Validation\NumberValidator;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Validation\Errors\NotNumberValidationError;
use Seier\Resting\Validation\Errors\NullableValidationError;
use Seier\Resting\Validation\Errors\IntNotPositiveValidationError;
use Seier\Resting\Validation\Secondary\Numeric\PositiveNumberValidationError;

class NumberFieldTest extends TestCase
{

    use AssertsErrors;

    private NumberField $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new NumberField;
    }

    public function testGetCanReturnNull()
    {
        $this->assertNull($this->instance->get());
    }

    public function getGetCanReturnInt()
    {
        $this->instance->set(3);
        $this->assertEquals(3, $this->instance->get());
    }

    public function getGetCanReturnFloat()
    {
        $this->instance->set(3.1);
        $this->assertEquals(3.1, $this->instance->get());
    }

    public function testSetWhenGivenInt()
    {
        $this->instance->set($expected = $this->faker->randomNumber());

        $this->assertEquals($expected, $this->instance->get());
    }

    public function testSetWhenGivenFloat()
    {
        $this->instance->set($expected = $this->faker->randomFloat());

        $this->assertEquals($expected, $this->instance->get());
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
        $validationException = $this->assertThrowsValidationException(function () {
            $this->instance->set(null);
        });

        $this->assertCount(1, $validationException->getErrors());
        $this->assertHasError($validationException, NullableValidationError::class);
    }

    public function testSetThrowsWhenGivenIncorrectType()
    {
        $validationException = $this->assertThrowsValidationException(function () {
            $this->instance->set('');
        });

        $this->assertCount(1, $validationException->getErrors());
        $this->assertHasError($validationException, NotNumberValidationError::class);
    }

    public function testSetThrowsWhenValueDoesNotPassValidation()
    {
        $this->instance->onValidator(function (NumberValidator $validator) {
            $validator->positive();
        });

        $validationException = $this->assertThrowsValidationException(function () {
            $this->instance->set(-1);
        });

        $this->assertCount(1, $validationException->getErrors());
        $this->assertHasError($validationException, PositiveNumberValidationError::class);
    }

    public function testSetDoesNotThrowWhenValuePassesValidation()
    {
        $this->instance->onValidator(function (NumberValidator $validator) {
            $validator->positive();
        });

        $this->assertDoesNotThrowValidationException(function () {
            $this->instance->set(1);
        });
    }
}
