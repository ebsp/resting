<?php


namespace Seier\Resting\Tests\Validation;


use Seier\Resting\Fields\Time;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\Validation\TimeValidator;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Tests\Meta\MockSecondaryValidator;
use Seier\Resting\Tests\Meta\MockSecondaryValidationError;
use Seier\Resting\Validation\Errors\NotTimeValidationError;

class TimeValidatorTest extends TestCase
{

    use AssertsErrors;

    private TimeValidator $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new TimeValidator();
    }

    public function testValidateTimeInstance()
    {
        $this->assertEmpty($this->instance->validate(new Time()));
    }

    public function testValidateIncorrectType()
    {
        $this->assertNotEmpty($errors = $this->instance->validate(''));
        $this->assertHasError($errors, NotTimeValidationError::class);
    }

    public function testValidateNullW()
    {
        $this->assertNotEmpty($errors = $this->instance->validate(null));
        $this->assertHasError($errors, NotTimeValidationError::class);
    }

    public function testValidateWithRegisteredSecondaryValidationThatPasses()
    {
        $this->instance->withValidator(MockSecondaryValidator::pass());

        $this->assertEmpty($this->instance->validate(new Time()));
    }

    public function testValidateWithRegisteredSecondaryValidationThatFails()
    {
        $this->instance->withValidator(MockSecondaryValidator::fail());

        $this->assertNotEmpty($errors = $this->instance->validate(new Time()));
        $this->assertHasError($errors, MockSecondaryValidationError::class);
    }
}