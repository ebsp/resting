<?php


namespace Seier\Resting\Tests\Validation\Secondary\String;


use Seier\Resting\Tests\TestCase;
use Jchook\AssertThrows\AssertThrows;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Exceptions\RestingInternalException;
use Seier\Resting\Validation\Secondary\String\StringMinLengthValidator;
use Seier\Resting\Validation\Secondary\String\StringMinLengthValidationError;

class StringMinLengthValidatorTest extends TestCase
{

    use AssertThrows;
    use AssertsErrors;

    use AssertThrows;
    use AssertsErrors;

    public function testWhenLengthEqualsMinLength()
    {
        $instance = new StringMinLengthValidator(2);

        $this->assertEmpty($instance->validate('ab'));
    }

    public function testWhenLengthGreaterThanMinLength()
    {
        $instance = new StringMinLengthValidator(2);

        $this->assertEmpty($instance->validate('abc'));
    }

    public function testWhenLengthLessThanMinLength()
    {
        $instance = new StringMinLengthValidator(2);

        $this->assertNotEmpty($errors = $instance->validate('a'));
        $this->assertHasError($errors, StringMinLengthValidationError::class);
    }

    public function testWhenNotProvidedString()
    {
        $instance = new StringMinLengthValidator(2);

        $this->assertThrows(RestingInternalException::class, function () use ($instance) {
            $instance->validate(0);
        });
    }
}