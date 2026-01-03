<?php


namespace Seier\Resting\Tests\Validation;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Validation\BoolValidator;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Tests\Meta\MockSecondaryValidator;
use Seier\Resting\Tests\Meta\MockSecondaryValidationError;
use Seier\Resting\Validation\Errors\NotBoolValidationError;

class BoolValidatorTest extends TestCase
{

    use AssertsErrors;

    private BoolValidator $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new BoolValidator();
    }

    public function testValidateTrue()
    {
        $this->assertEmpty($this->instance->validate(true));
    }

    public function testValidateFalse()
    {
        $this->assertEmpty($this->instance->validate(false));
    }

    public function testValidateIncorrectType()
    {
        $this->assertNotEmpty($errors = $this->instance->validate(''));
        $this->assertHasError($errors, NotBoolValidationError::class);
    }

    public function testValidateNullW()
    {
        $this->assertNotEmpty($errors = $this->instance->validate(null));
        $this->assertHasError($errors, NotBoolValidationError::class);
    }

    public function testValidateWithRegisteredSecondaryValidationThatPasses()
    {
        $this->instance->withValidator(MockSecondaryValidator::pass());

        $this->assertEmpty($this->instance->validate(true));
    }

    public function testValidateWithRegisteredSecondaryValidationThatFails()
    {
        $this->instance->withValidator(MockSecondaryValidator::fail());

        $this->assertNotEmpty($errors = $this->instance->validate(true));
        $this->assertHasError($errors, MockSecondaryValidationError::class);
    }
}