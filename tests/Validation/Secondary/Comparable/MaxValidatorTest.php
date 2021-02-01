<?php


namespace Seier\Resting\Tests\Validation\Secondary\Comparable;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Validation\Secondary\Comparable\MaxValidator;
use Seier\Resting\Validation\Secondary\Comparable\MaxValidationError;

class MaxValidatorTest extends TestCase
{

    use AssertsErrors;

    private function create(int $max, bool $inclusive): MaxValidator
    {
        return new MaxValidator(
            $max,
            $inclusive,
            fn(int $value) => $value,
            fn(int $value) => $value,
        );
    }

    public function testInclusiveWhenEqualsMax()
    {
        $instance = $this->create(1, inclusive: true);

        $this->assertEmpty($instance->validate(1));
    }

    public function testInclusiveWhenGreaterThanMax()
    {
        $instance = $this->create(1, inclusive: true);

        $this->assertNotEmpty($errors = $instance->validate(2));
        $this->assertHasError($errors, MaxValidationError::class);
    }

    public function testInclusiveWhenLessThanMax()
    {
        $instance = $this->create(1, inclusive: true);

        $this->assertEmpty($instance->validate(0));
    }

    public function testExclusiveWhenEqualsMax()
    {
        $instance = $this->create(1, inclusive: false);

        $this->assertNotEmpty($errors = $instance->validate(1));
        $this->assertHasError($errors, MaxValidationError::class);
    }

    public function testExclusiveWhenGreaterThanMax()
    {
        $instance = $this->create(1, inclusive: false);

        $this->assertNotEmpty($errors = $instance->validate(2));
        $this->assertHasError($errors, MaxValidationError::class);
    }

    public function testExclusiveWhenLessThanMax()
    {
        $instance = $this->create(1, inclusive: false);

        $this->assertEmpty($instance->validate(0));
    }
}