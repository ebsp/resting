<?php


namespace Seier\Resting\Tests\Validation\Secondary\String;


use Seier\Resting\Tests\TestCase;
use Jchook\AssertThrows\AssertThrows;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Exceptions\RestingInternalException;
use Seier\Resting\Validation\Secondary\String\StringLengthValidator;
use Seier\Resting\Validation\Secondary\String\StringLengthValidationError;

class StringLengthValidatorTest extends TestCase
{

    use AssertThrows;
    use AssertsErrors;

    public function testWhenLengthEqualsExpectedLength()
    {
        $instance = new StringLengthValidator(2);

        $this->assertEmpty($instance->validate('ab'));
    }

    public function testWhenLengthGreaterThanExpectedLength()
    {
        $instance = new StringLengthValidator(2);

        $this->assertNotEmpty($errors = $instance->validate('abc'));
        $this->assertHasError($errors, StringLengthValidationError::class);
    }

    public function testWhenLengthLessThanExpectedLength()
    {
        $instance = new StringLengthValidator(2);

        $this->assertNotEmpty($errors = $instance->validate('a'));
        $this->assertHasError($errors, StringLengthValidationError::class);
    }

    public function testWhenNotProvidedString()
    {
        $instance = new StringLengthValidator(2);

        $this->assertThrows(RestingInternalException::class, function () use ($instance) {
            $instance->validate(0);
        });
    }
}