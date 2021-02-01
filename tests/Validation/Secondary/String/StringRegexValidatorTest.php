<?php


namespace Seier\Resting\Tests\Validation\Secondary\String;


use Seier\Resting\Tests\TestCase;
use Jchook\AssertThrows\AssertThrows;
use Seier\Resting\Exceptions\RestingInternalException;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Validation\Secondary\String\StringRegexValidator;
use Seier\Resting\Validation\Secondary\String\StringRegexValidationError;

class StringRegexValidatorTest extends TestCase
{

    use AssertThrows;
    use AssertsErrors;

    public function testWhenProvidedMatchingString()
    {
        $instance = new StringRegexValidator('/^[a-z]+$/');

        $this->assertEmpty($instance->validate('abc'));
    }

    public function testWhenProvidedNonMatchingString()
    {
        $instance = new StringRegexValidator('/^[a-z]+$/');

        $this->assertNotEmpty($errors = $instance->validate('a_c'));
        $this->assertHasError($errors, StringRegexValidationError::class);
    }

    public function testWhenNotProvidedString()
    {
        $instance = new StringRegexValidator('/.*/');

        $this->assertThrows(RestingInternalException::class, function () use ($instance) {
            $instance->validate(0);
        });
    }
}