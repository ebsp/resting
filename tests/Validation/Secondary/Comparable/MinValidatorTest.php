<?php


namespace Seier\Resting\Tests\Validation\Secondary\Comparable;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Validation\Secondary\Comparable\MinValidator;
use Seier\Resting\Validation\Secondary\Comparable\MinValidationError;

class MinValidatorTest extends TestCase
{

    use AssertsErrors;

    private function create(int $min, bool $inclusive): MinValidator
    {
        return new MinValidator(
            $min,
            $inclusive,
            fn(int $value) => $value,
            fn(int $value) => $value,
        );
    }

    public function testWhenInclusiveEqualsMin()
    {
        $instance = $this->create(1, inclusive: true);

        $this->assertEmpty($instance->validate(1));
    }

    public function testWhenInclusiveGreaterThanMin()
    {
        $instance = $this->create(1, inclusive: true);

        $this->assertEmpty($instance->validate(2));
    }

    public function testWhenInclusiveLessThanMin()
    {
        $instance = $this->create(1, inclusive: true);

        $this->assertNotEmpty($errors = $instance->validate(0));
        $this->assertHasError($errors, MinValidationError::class);
    }

    public function testWhenExclusiveEqualsMin()
    {
        $instance = $this->create(1, inclusive: false);

        $this->assertNotEmpty($errors = $instance->validate(1));
        $this->assertHasError($errors, MinValidationError::class);
    }

    public function testWhenExclusiveGreaterThanMin()
    {
        $instance = $this->create(1, inclusive: false);

        $this->assertEmpty($instance->validate(2));
    }

    public function testWhenExclusiveLessThanMin()
    {
        $instance = $this->create(1, inclusive: false);

        $this->assertNotEmpty($errors = $instance->validate(0));
        $this->assertHasError($errors, MinValidationError::class);
    }
}