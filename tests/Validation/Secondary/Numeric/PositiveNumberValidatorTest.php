<?php


namespace Seier\Resting\Tests\Validation\Secondary\Numeric;


use Seier\Resting\Tests\TestCase;
use Jchook\AssertThrows\AssertThrows;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Exceptions\RestingInternalException;
use Seier\Resting\Validation\Secondary\Numeric\PositiveNumberValidator;
use Seier\Resting\Validation\Secondary\Numeric\PositiveNumberValidationError;

class PositiveNumberValidatorTest extends TestCase
{

    use AssertsErrors;
    use AssertThrows;

    public function testWhenProvidedZeroInteger()
    {
        $instance = new PositiveNumberValidator();

        $this->assertNotEmpty($errors = $instance->validate(0));
        $this->assertHasError($errors, PositiveNumberValidationError::class);
    }

    public function testWhenProvidedZeroFloat()
    {
        $instance = new PositiveNumberValidator();

        $this->assertNotEmpty($errors = $instance->validate(0.0));
        $this->assertHasError($errors, PositiveNumberValidationError::class);
    }

    public function testWhenProvidedPositiveInteger()
    {
        $instance = new PositiveNumberValidator();

        $this->assertEmpty($errors = $instance->validate(1));
    }

    public function testWhenProvidedPositiveFloat()
    {
        $instance = new PositiveNumberValidator();

        $this->assertEmpty($errors = $instance->validate(0.001));
    }

    public function testWhenProvidedNegativeInteger()
    {
        $instance = new PositiveNumberValidator();

        $this->assertNotEmpty($errors = $instance->validate(-1));
        $this->assertHasError($errors, PositiveNumberValidationError::class);
    }

    public function testWhenProvidedNegativeFloat()
    {
        $instance = new PositiveNumberValidator();

        $this->assertNotEmpty($errors = $instance->validate(-0.001));
        $this->assertHasError($errors, PositiveNumberValidationError::class);
    }

    public function testWhenNotProvidedNumber()
    {
        $instance = new PositiveNumberValidator();

        $this->assertThrows(RestingInternalException::class, function () use ($instance) {
            $instance->validate('');
        });
    }
}