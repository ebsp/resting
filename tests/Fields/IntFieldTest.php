<?php

namespace Seier\Resting\Tests\Fields;

use Seier\Resting\Tests\TestCase;
use Seier\Resting\Fields\IntField;
use Seier\Resting\Validation\IntValidator;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Validation\Errors\NotIntValidationError;
use Seier\Resting\Validation\Errors\NullableValidationError;
use Seier\Resting\Validation\Secondary\Numeric\PositiveNumberValidationError;

class IntFieldTest extends TestCase
{

    use AssertsErrors;

    private IntField $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new IntField;
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

    public function testSetWhenGivenInt()
    {
        $this->instance->set($expected = $this->faker->randomNumber());

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
        $this->assertHasError($validationException, NotIntValidationError::class);
    }

    public function testSetThrowsWhenValueDoesNotPassValidation()
    {
        $this->instance->onValidator(function (IntValidator $validator) {
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
        $this->instance->onValidator(function (IntValidator $validator) {
            $validator->positive();
        });

        $this->assertDoesNotThrowValidationException(function () {
            $this->instance->set(1);
        });
    }
}
