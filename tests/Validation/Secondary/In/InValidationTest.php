<?php


namespace Seier\Resting\Tests\Validation\Secondary\In;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Tests\Meta\MockPrimaryValidator;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Validation\Secondary\In\InValidationError;

class InValidationTest extends TestCase
{

    use AssertsErrors;

    private MockPrimaryValidator $validator;
    private InValidationTestBench $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->validator = new MockPrimaryValidator();
        $this->instance = new InValidationTestBench($this->validator);
    }

    public function testInWhenPasses()
    {
        $this->instance->in([1, 2, 3]);

        $this->assertEmpty($this->validator->validate(2));
    }

    public function testInWhenFails()
    {
        $this->instance->in([1, 2, 3]);

        $this->assertNotEmpty($errors = $this->validator->validate(4));
        $this->assertHasError($errors, InValidationError::class);
    }
}