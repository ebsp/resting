<?php


namespace Seier\Resting\Tests\Validation\Secondary\Arrays;


use Seier\Resting\Tests\TestCase;
use Jchook\AssertThrows\AssertThrows;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Exceptions\RestingInternalException;
use Seier\Resting\Validation\Secondary\Arrays\ArraySizeValidator;
use Seier\Resting\Validation\Secondary\Arrays\ArraySizeValidationError;

class ArraySizeValidatorTest extends TestCase
{

    use AssertsErrors;
    use AssertThrows;

    public function testWhenSizeEqualsExpectedSize()
    {
        $instance = new ArraySizeValidator(1);

        $this->assertEmpty($instance->validate([1]));
    }

    public function testWhenSizeGreaterThanExpectedSize()
    {
        $instance = new ArraySizeValidator(1);

        $this->assertNotEmpty($errors = $instance->validate([1, 2]));
        $this->assertHasError($errors, ArraySizeValidationError::class);
    }

    public function testWhenSizeLessThanExpectedSize()
    {
        $instance = new ArraySizeValidator(1);

        $this->assertNotEmpty($errors = $instance->validate([]));
        $this->assertHasError($errors, ArraySizeValidationError::class);
    }

    public function testWhenNotProvidedArray()
    {
        $instance = new ArraySizeValidator(1);

        $this->assertThrows(RestingInternalException::class, function () use ($instance) {
            $instance->validate(1);
        });
    }
}