<?php


namespace Seier\Resting\Tests\Validation;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Validation\ArrayValidator;
use Seier\Resting\Tests\Meta\MockPrimaryValidator;
use Seier\Resting\Tests\Meta\MockSecondaryValidator;
use Seier\Resting\Tests\Meta\MockPrimaryValidationError;
use Seier\Resting\Tests\Meta\MockSecondaryValidationError;
use Seier\Resting\Validation\Errors\NotArrayValidationError;

class ArrayValidatorTest extends TestCase
{

    use AssertsErrors;

    private ArrayValidator $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new ArrayValidator();
    }

    public function testValidateEmptyArray()
    {
        $this->assertEmpty($this->instance->validate([]));
    }

    public function testValidateNonEmptyArray()
    {
        $this->assertEmpty($this->instance->validate([1, 2, 3]));
    }

    public function testValidateIncorrectType()
    {
        $this->assertNotEmpty($errors = $this->instance->validate(''));
        $this->assertHasError($errors, NotArrayValidationError::class);
    }

    public function testValidateNull()
    {
        $this->assertNotEmpty($errors = $this->instance->validate(null));
        $this->assertHasError($errors, NotArrayValidationError::class);
    }

    public function testValidateWhenElementValidationPasses()
    {
        $this->instance->setElementValidator(MockPrimaryValidator::pass());

        $this->assertEmpty($this->instance->validate([1, 2, 3]));
    }

    public function testValidateWhenElementValidationFails()
    {
        $this->instance->setElementValidator(MockPrimaryValidator::passWhenMatches(2));

        $array = [1, 2, 3];
        $this->assertNotEmpty($errors = $this->instance->validate($array));
        $this->assertCount(2, $errors);
        $this->assertHasError($errors, MockPrimaryValidationError::class, 0);
        $this->assertHasError($errors, MockPrimaryValidationError::class, 2);
    }

    public function testValidateWithRegisteredSecondaryValidationThatPasses()
    {
        $this->instance->withValidator(MockSecondaryValidator::pass());

        $this->assertEmpty($this->instance->validate([]));
    }

    public function testValidateWithRegisteredSecondaryValidationThatFails()
    {
        $this->instance->withValidator(MockSecondaryValidator::fail());

        $this->assertNotEmpty($errors = $this->instance->validate([]));
        $this->assertHasError($errors, MockSecondaryValidationError::class);
    }
}