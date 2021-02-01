<?php


namespace Seier\Resting\Tests\Validation\Secondary\Enum;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Validation\Secondary\Enum\EnumValidator;
use Seier\Resting\Validation\Secondary\Enum\EnumValidationError;

class EnumValidatorTest extends TestCase
{

    use AssertsErrors;

    public function testValidateUsingIntegers()
    {
        $instance = new EnumValidator([1, 3]);

        $this->assertEmpty($instance->validate(1));
        $this->assertNotEmpty($instance->validate(2));
        $this->assertEmpty($instance->validate(3));
    }

    public function testValidateUsingStrings()
    {
        $instance = new EnumValidator(['a', 'c']);

        $this->assertEmpty($instance->validate('a'));
        $this->assertNotEmpty($instance->validate('b'));
        $this->assertEmpty($instance->validate('c'));
    }

    public function testValidateUsingFloats()
    {
        $instance = new EnumValidator([1.0, 1.2]);

        $this->assertEmpty($instance->validate(1.0));
        $this->assertNotEmpty($instance->validate(1.1));
        $this->assertEmpty($instance->validate(1.2));
    }

    public function testValidateUsingBooleans()
    {
        $instance = new EnumValidator([true]);

        $this->assertEmpty($instance->validate(true));
        $this->assertNotEmpty($instance->validate(false));
    }

    public function testUsesStrictComparison()
    {
        $instance = new EnumValidator([1]);

        $this->assertNotEmpty($errors = $instance->validate('1'));
        $this->assertHasError($errors, EnumValidationError::class);
    }

    public function testWhenThereAreNoOptions()
    {
        $instance = new EnumValidator([]);

        $this->assertNotEmpty($errors = $instance->validate('1'));
        $this->assertHasError($errors, EnumValidationError::class);
    }
}