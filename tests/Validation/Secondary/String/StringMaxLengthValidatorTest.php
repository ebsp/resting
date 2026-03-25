<?php


namespace Seier\Resting\Tests\Validation\Secondary\String;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Exceptions\RestingInternalException;
use Seier\Resting\Validation\Secondary\String\StringMaxLengthValidator;
use Seier\Resting\Validation\Secondary\String\StringMaxLengthValidationError;

class StringMaxLengthValidatorTest extends TestCase
{

    use AssertsErrors;

    public function testWhenLengthEqualsMaxLength()
    {
        $instance = new StringMaxLengthValidator(2);

        $this->assertEmpty($instance->validate('ab'));
    }

    public function testWhenLengthGreaterThanMaxLength()
    {
        $instance = new StringMaxLengthValidator(2);

        $this->assertNotEmpty($errors = $instance->validate('abc'));
        $this->assertHasError($errors, StringMaxLengthValidationError::class);
    }

    public function testWhenLengthLessThanMaxLength()
    {
        $instance = new StringMaxLengthValidator(2);

        $this->assertEmpty($instance->validate('a'));
    }

    public function testWhenNotProvidedString()
    {
        $instance = new StringMaxLengthValidator(2);

        $this->assertThrows(RestingInternalException::class, function () use ($instance) {
            $instance->validate(0);
        });
    }
}