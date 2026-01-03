<?php


namespace Seier\Resting\Tests\Validation;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Validation\StringValidator;
use Seier\Resting\Tests\Meta\MockSecondaryValidator;
use Seier\Resting\Tests\Meta\MockSecondaryValidationError;
use Seier\Resting\Validation\Errors\NotStringValidationError;

class StringValidatorTest extends TestCase
{

    use AssertsErrors;

    private StringValidator $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new StringValidator();
    }

    public function testValidateEmptyString()
    {
        $this->assertEmpty($this->instance->validate(''));
    }

    public function testValidateWord()
    {
        $this->assertEmpty($this->instance->validate($this->faker->word));
    }

    public function testValidateIncorrectType()
    {
        $this->assertNotEmpty($errors = $this->instance->validate(1));
        $this->assertHasError($errors, NotStringValidationError::class);
    }

    public function testValidateNullW()
    {
        $this->assertNotEmpty($errors = $this->instance->validate(null));
        $this->assertHasError($errors, NotStringValidationError::class);
    }

    public function testValidateWithRegisteredSecondaryValidationThatPasses()
    {
        $this->instance->withValidator(MockSecondaryValidator::pass());

        $this->assertEmpty($this->instance->validate($this->faker->word));
    }

    public function testValidateWithRegisteredSecondaryValidationThatFails()
    {
        $this->instance->withValidator(MockSecondaryValidator::fail());

        $this->assertNotEmpty($errors = $this->instance->validate($this->faker->word));
        $this->assertHasError($errors, MockSecondaryValidationError::class);
    }
}