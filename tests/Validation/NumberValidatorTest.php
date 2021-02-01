<?php


namespace Seier\Resting\Tests\Validation;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Validation\NumberValidator;
use Seier\Resting\Tests\Meta\MockSecondaryValidator;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Tests\Meta\MockSecondaryValidationError;
use Seier\Resting\Validation\Errors\NotNumberValidationError;

class NumberValidatorTest extends TestCase
{

    use AssertsErrors;

    private NumberValidator $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new NumberValidator();
    }

    public function testValidateZeroInteger()
    {
        $this->assertEmpty($this->instance->validate(0));
    }

    public function testValidatePositiveInteger()
    {
        $this->assertEmpty($this->instance->validate(1));
    }

    public function testValidateNegativeInteger()
    {
        $this->assertEmpty($this->instance->validate(-1));
    }

    public function testValidateZeroFloat()
    {
        $this->assertEmpty($this->instance->validate(0.0));
    }

    public function testValidatePositiveFloat()
    {
        $this->assertEmpty($this->instance->validate(1.9));
    }

    public function testValidateNegativeFloat()
    {
        $this->assertEmpty($this->instance->validate(-1.9));
    }

    public function testValidateIncorrectType()
    {
        $this->assertNotEmpty($errors = $this->instance->validate(''));
        $this->assertHasError($errors, NotNumberValidationError::class);
    }

    public function testValidateNullW()
    {
        $this->assertNotEmpty($errors = $this->instance->validate(null));
        $this->assertHasError($errors, NotNumberValidationError::class);
    }

    public function testValidateWithRegisteredSecondaryValidationThatPasses()
    {
        $this->instance->withValidator(MockSecondaryValidator::pass());

        $this->assertEmpty($this->instance->validate(1));
    }

    public function testValidateWithRegisteredSecondaryValidationThatFails()
    {
        $this->instance->withValidator(MockSecondaryValidator::fail());

        $this->assertNotEmpty($errors = $this->instance->validate(1));
        $this->assertHasError($errors, MockSecondaryValidationError::class);
    }
}