<?php


namespace Seier\Resting\Tests\Validation\Secondary\Numeric;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Tests\Meta\MockPrimaryValidator;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;
use Seier\Resting\Validation\Secondary\Comparable\MinValidationError;
use Seier\Resting\Validation\Secondary\Comparable\MaxValidationError;

class NumericValidationTest extends TestCase
{

    use AssertsErrors;

    private float $epsilon;
    private MockPrimaryValidator $validator;
    private NumericValidationTestBench $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->epsilon = 0.00001;
        $this->validator = new MockPrimaryValidator();
        $this->instance = new NumericValidationTestBench($this->validator);
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        return $this->validator;
    }

    public function testMinWhenPasses()
    {
        $this->instance->min(1);

        $this->assertEmpty($this->validator->validate(1));
        $this->assertEmpty($this->validator->validate(1 + $this->epsilon));
    }

    public function testMinWhenFails()
    {
        $this->instance->min(1);

        $this->assertNotEmpty($errors = $this->validator->validate(1 - $this->epsilon));
        $this->assertHasError($errors, MinValidationError::class);
    }

    public function testMaxWhenPasses()
    {
        $this->instance->max(1);

        $this->assertEmpty($this->validator->validate(1 - $this->epsilon));
        $this->assertEmpty($this->validator->validate(1));
    }

    public function testMaxWhenFails()
    {
        $this->instance->max(1);

        $this->assertNotEmpty($errors = $this->validator->validate(1 + $this->epsilon));
        $this->assertHasError($errors, MaxValidationError::class);
    }

    public function testLessThanWhenPasses()
    {
        $this->instance->lessThan(1);

        $this->assertEmpty($this->validator->validate(1 - $this->epsilon));
    }

    public function testLessThanWhenFails()
    {
        $this->instance->lessThan(1);

        $this->assertNotEmpty($errors = $this->validator->validate(1));
        $this->assertHasError($errors, MaxValidationError::class);

        $this->assertNotEmpty($errors = $this->validator->validate(1 + $this->epsilon));
        $this->assertHasError($errors, MaxValidationError::class);
    }

    public function testGreaterThanWhenPasses()
    {
        $this->instance->greaterThan(1);

        $this->assertEmpty($this->validator->validate(1 + $this->epsilon));
    }

    public function testGreaterThanWhenFails()
    {
        $this->instance->greaterThan(1);

        $this->assertNotEmpty($errors = $this->validator->validate(1));
        $this->assertHasError($errors, MinValidationError::class);

        $this->assertNotEmpty($errors = $this->validator->validate(1 - $this->epsilon));
        $this->assertHasError($errors, MinValidationError::class);
    }

    public function testBetweenWhenPasses()
    {
        $this->instance->between(1, 2);

        $this->assertEmpty($this->validator->validate(1));
        $this->assertEmpty($this->validator->validate(1.5));
        $this->assertEmpty($this->validator->validate(2));
    }

    public function testBetweenWhenFails()
    {
        $this->instance->between(1, 2);

        $this->assertNotEmpty($errors = $this->validator->validate(1 - $this->epsilon));
        $this->assertHasError($errors, MinValidationError::class);

        $this->assertNotEmpty($errors = $this->validator->validate(1 - $this->epsilon));
        $this->assertHasError($errors, MinValidationError::class);
    }
}