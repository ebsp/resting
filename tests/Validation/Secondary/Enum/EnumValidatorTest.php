<?php


namespace Seier\Resting\Tests\Validation\Secondary\Enum;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Validation\Secondary\In\InValidator;
use Seier\Resting\Validation\Secondary\In\InValidationError;

class EnumValidatorTest extends TestCase
{

    use AssertsErrors;

    public function testValidateUsingIntegers()
    {
        $instance = new InValidator([1, 3]);

        $this->assertEmpty($instance->validate(1));
        $this->assertNotEmpty($instance->validate(2));
        $this->assertEmpty($instance->validate(3));
    }

    public function testValidateUsingStrings()
    {
        $instance = new InValidator(['a', 'c']);

        $this->assertEmpty($instance->validate('a'));
        $this->assertNotEmpty($instance->validate('b'));
        $this->assertEmpty($instance->validate('c'));
    }

    public function testValidateUsingFloats()
    {
        $instance = new InValidator([1.0, 1.2]);

        $this->assertEmpty($instance->validate(1.0));
        $this->assertNotEmpty($instance->validate(1.1));
        $this->assertEmpty($instance->validate(1.2));
    }

    public function testValidateUsingBooleans()
    {
        $instance = new InValidator([true]);

        $this->assertEmpty($instance->validate(true));
        $this->assertNotEmpty($instance->validate(false));
    }

    public function testUsesStrictComparison()
    {
        $instance = new InValidator([1]);

        $this->assertNotEmpty($errors = $instance->validate('1'));
        $this->assertHasError($errors, InValidationError::class);
    }

    public function testWhenThereAreNoOptions()
    {
        $instance = new InValidator([]);

        $this->assertNotEmpty($errors = $instance->validate('1'));
        $this->assertHasError($errors, InValidationError::class);
    }
}