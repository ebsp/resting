<?php

namespace Seier\Resting\Tests\Validation\Secondary\Numeric;

use Seier\Resting\Tests\TestCase;
use Jchook\AssertThrows\AssertThrows;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Validation\Secondary\Numeric\DecimalCountValidator;
use Seier\Resting\Validation\Secondary\Numeric\DecimalCountValidationError;

class DecimalCountValidatorTest extends TestCase
{
    use AssertsErrors;
    use AssertThrows;

    public function testWhenLessThanMinDecimals()
    {
        $instance = new DecimalCountValidator(minDecimals: 2);

        $this->assertNotEmpty($errors = $instance->validate(1.1));
        $this->assertHasError($errors, DecimalCountValidationError::class);
    }

    public function testWhenEqualsMinDecimals()
    {
        $instance = new DecimalCountValidator(minDecimals: 2);

        $this->assertEmpty($instance->validate(1.13));
    }

    public function testIntegersAreAcceptedWhenMinDecimalsIsZero()
    {
        $instance = new DecimalCountValidator(minDecimals: 0);

        $this->assertEmpty($instance->validate(12));
    }

    public function testWhenGreaterThanMaxDecimals()
    {
        $instance = new DecimalCountValidator(maxDecimals: 2);

        $this->assertNotEmpty($errors = $instance->validate(1.133));
        $this->assertHasError($errors, DecimalCountValidationError::class);
    }

    public function testWhenEqualsMaxDecimals()
    {
        $instance = new DecimalCountValidator(maxDecimals: 3);

        $this->assertEmpty($instance->validate(1.133));
    }

    public function testWhenLessThanMaxDecimals()
    {
        $instance = new DecimalCountValidator(maxDecimals: 3);

        $this->assertEmpty($instance->validate(1.33));
    }
}