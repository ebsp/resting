<?php


namespace Seier\Resting\Tests\Validation\Secondary\Arrays;


use Seier\Resting\Tests\TestCase;
use Jchook\AssertThrows\AssertThrows;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Exceptions\RestingInternalException;
use Seier\Resting\Validation\Secondary\Arrays\ArrayMaxSizeValidator;
use Seier\Resting\Validation\Secondary\Arrays\ArrayMaxSizeValidationError;

class ArrayMaxSizeValidatorTest extends TestCase
{

    use AssertsErrors;
    use AssertThrows;

    public function testWhenSizeEqualsMax()
    {
        $instance = new ArrayMaxSizeValidator(1);

        $this->assertEmpty($instance->validate([1]));
    }

    public function testWhenSizeGreaterThanMax()
    {
        $instance = new ArrayMaxSizeValidator(1);

        $this->assertNotEmpty($errors = $instance->validate([1, 2]));
        $this->assertHasError($errors, ArrayMaxSizeValidationError::class);
    }

    public function testWhenSizeLessThanMax()
    {
        $instance = new ArrayMaxSizeValidator(1);

        $this->assertEmpty($instance->validate([]));
    }

    public function testWhenNotProvidedArray()
    {
        $instance = new ArrayMaxSizeValidator(1);

        $this->assertThrows(RestingInternalException::class, function () use ($instance) {
            $instance->validate(1);
        });
    }
}