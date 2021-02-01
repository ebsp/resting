<?php


namespace Seier\Resting\Tests\Validation\Secondary\Arrays;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Tests\Meta\MockPrimaryValidator;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Validation\Secondary\Arrays\ArraySizeValidationError;
use Seier\Resting\Validation\Secondary\Arrays\ArrayMinSizeValidationError;
use Seier\Resting\Validation\Secondary\Arrays\ArrayMaxSizeValidationError;

class ArrayValidationTest extends TestCase
{

    use AssertsErrors;

    private MockPrimaryValidator $validator;
    private ArrayValidationTestBench $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->validator = new MockPrimaryValidator();
        $this->instance = new ArrayValidationTestBench($this->validator);
    }

    public function testMaxSizeWhenPasses()
    {
        $this->instance->maxSize(1);

        $this->assertEmpty($this->validator->validate([1]));
    }

    public function testMaxSizeWhenFails()
    {
        $this->instance->maxSize(1);

        $this->assertNotEmpty($errors = $this->validator->validate([1, 2]));
        $this->assertHasError($errors, ArrayMaxSizeValidationError::class);
    }

    public function testMinSizeWhenPasses()
    {
        $this->instance->minSize(1);

        $this->assertEmpty($this->validator->validate([1]));
    }

    public function testMinSizeWhenFails()
    {
        $this->instance->minSize(1);

        $this->assertNotEmpty($errors = $this->validator->validate([]));
        $this->assertHasError($errors, ArrayMinSizeValidationError::class);
    }

    public function testSizeWhenPasses()
    {
        $this->instance->size(1);

        $this->assertEmpty($this->validator->validate([1]));
    }

    public function testSizeWhenFails()
    {
        $this->instance->size(1);

        $this->assertNotEmpty($errors = $this->validator->validate([]));
        $this->assertHasError($errors, ArraySizeValidationError::class);
    }

    public function testEmptyWhenPasses()
    {
        $this->instance->empty();

        $this->assertEmpty($this->validator->validate([]));
    }

    public function testEmptyWhenFails()
    {
        $this->instance->empty();

        $this->assertNotEmpty($errors = $this->validator->validate([1]));
        $this->assertHasError($errors, ArraySizeValidationError::class);
    }

    public function testNotEmptyWhenPasses()
    {
        $this->instance->notEmpty();

        $this->assertEmpty($this->validator->validate([1]));
    }

    public function testNotEmptyWhenFails()
    {
        $this->instance->notEmpty();

        $this->assertNotEmpty($errors = $this->validator->validate([]));
        $this->assertHasError($errors, ArrayMinSizeValidationError::class);
    }
}