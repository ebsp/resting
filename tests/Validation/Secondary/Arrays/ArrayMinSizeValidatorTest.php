<?php


namespace Seier\Resting\Tests\Validation\Secondary\Arrays;


use Seier\Resting\Tests\TestCase;
use Jchook\AssertThrows\AssertThrows;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Exceptions\RestingInternalException;
use Seier\Resting\Validation\Secondary\Arrays\ArrayMinSizeValidator;
use Seier\Resting\Validation\Secondary\Arrays\ArrayMinSizeValidationError;

class ArrayMinSizeValidatorTest extends TestCase
{

    use AssertsErrors;
    use AssertThrows;

    public function testWhenSizeEqualsMin()
    {
        $instance = new ArrayMinSizeValidator(1);

        $this->assertEmpty($instance->validate([1]));
    }

    public function testWhenSizeGreaterThanMin()
    {
        $instance = new ArrayMinSizeValidator(1);

        $this->assertEmpty($instance->validate([1, 2]));
    }

    public function testWhenSizeLessThanMin()
    {
        $instance = new ArrayMinSizeValidator(1);

        $this->assertNotEmpty($errors = $instance->validate([]));
        $this->assertHasError($errors, ArrayMinSizeValidationError::class);
    }

    public function testWhenNotProvidedArray()
    {
        $instance = new ArrayMinSizeValidator(1);

        $this->assertThrows(RestingInternalException::class, function () use ($instance) {
            $instance->validate(1);
        });
    }
}