<?php


namespace Seier\Resting\Tests\Validation\Secondary\Anonymous;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Tests\Meta\MockPrimaryValidator;
use Seier\Resting\Validation\Secondary\Anonymous\AnonymousValidationError;

class AnonymousValidationTest extends TestCase
{

    use AssertsErrors;

    private string $description;
    private MockPrimaryValidator $validator;
    private AnonymousValidationTestBench $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->description = $this->faker->text;
        $this->validator = new MockPrimaryValidator();
        $this->instance = new AnonymousValidationTestBench($this->validator);
    }

    public function testValidateThatWhenPasses()
    {
        $this->instance->validateThat($this->description, fn() => true);

        $this->assertEquals([], $this->validator->validate(null));
    }

    public function testValidateThatWhenFails()
    {
        $this->instance->validateThat($this->description, fn() => false);

        $this->assertCount(1, $errors = $this->validator->validate(null));
        $this->assertHasError($errors, AnonymousValidationError::class);
    }
}