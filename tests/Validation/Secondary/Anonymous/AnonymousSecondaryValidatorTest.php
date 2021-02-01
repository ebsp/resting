<?php


namespace Seier\Resting\Tests\Validation\Secondary\Anonymous;


use Seier\Resting\Tests\TestCase;
use Jchook\AssertThrows\AssertThrows;
use Seier\Resting\Exceptions\RestingDefinitionException;
use Seier\Resting\Validation\Secondary\Anonymous\AnonymousValidationError;
use Seier\Resting\Validation\Secondary\Anonymous\AnonymousSecondaryValidator;

class AnonymousSecondaryValidatorTest extends TestCase
{

    use AssertThrows;

    private string $description;

    public function setUp(): void
    {
        parent::setUp();

        $this->description = $this->faker->text(50);
    }

    public function testDescriptionReturnsProvidedDescription()
    {
        $instance = new AnonymousSecondaryValidator($this->description, fn() => true);

        $this->assertEquals($this->description, $instance->description());
    }

    public function testValidateProvidesValueToClosure()
    {
        $instance = new AnonymousSecondaryValidator($this->description, fn(int $value) => $value === 1);

        $this->assertEmpty($instance->validate(1));
        $this->assertNotEmpty($instance->validate(0));
        $this->assertNotEmpty($instance->validate(2));
    }

    public function testValidateWhenClosureReturnsNull()
    {
        $instance = new AnonymousSecondaryValidator($this->description, fn() => null);

        $this->assertEquals([], $instance->validate(null));
    }

    public function testValidateWhenClosureReturnsTrue()
    {
        $instance = new AnonymousSecondaryValidator($this->description, fn() => true);

        $this->assertEquals([], $instance->validate(null));
    }

    public function testValidateWhenClosureReturnsFalse()
    {
        $instance = new AnonymousSecondaryValidator($this->description, fn() => false);

        $this->assertCount(1, $errors = $instance->validate(null));
        $this->assertInstanceOf(AnonymousValidationError::class, $errors[0]);
        $this->assertStringContainsString($this->description, $errors[0]->getMessage());
    }

    public function testValidateWhenClosureReturnsValidationError()
    {
        $expected = new AnonymousValidationError('message');
        $instance = new AnonymousSecondaryValidator($this->description, fn() => $expected);

        $this->assertCount(1, $errors = $instance->validate(null));
        $this->assertInstanceOf(AnonymousValidationError::class, $errors[0]);
        $this->assertSame($expected, $errors[0]);
    }

    public function testValidateWhenClosureReturnsString()
    {
        $expectedMessage = $this->faker->text;
        $instance = new AnonymousSecondaryValidator($this->description, fn() => $expectedMessage);

        $this->assertCount(1, $errors = $instance->validate(null));
        $this->assertInstanceOf(AnonymousValidationError::class, $errors[0]);
        $this->assertEquals($expectedMessage, $errors[0]->getMessage());
    }

    public function testValidateWhenClosureReturnsUnknownValue()
    {
        $instance = new AnonymousSecondaryValidator($this->description, fn() => 1);

        $this->assertThrows(RestingDefinitionException::class, function () use ($instance) {
            $instance->validate(null);
        });
    }
}