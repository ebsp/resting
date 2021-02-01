<?php


namespace Seier\Resting\Tests\Validation\Secondary\CarbonPeriod;


use Carbon\CarbonPeriod;
use Carbon\CarbonInterval;
use Seier\Resting\Tests\TestCase;
use Jchook\AssertThrows\AssertThrows;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Exceptions\RestingInternalException;
use Seier\Resting\Validation\Secondary\CarbonPeriod\CarbonPeriodMinDurationValidator;
use Seier\Resting\Validation\Secondary\CarbonPeriod\CarbonPeriodMinDurationValidationError;

class CarbonPeriodMinDurationValidatorTest extends TestCase
{

    use AssertThrows;
    use AssertsErrors;

    public function testWhenPeriodDurationEqualsMin()
    {
        $instance = new CarbonPeriodMinDurationValidator(CarbonInterval::days(2));

        $start = now();
        $end = $start->copy()->addDays(2);
        $this->assertEmpty($instance->validate(CarbonPeriod::create($start, $end)));
    }

    public function testWhenPeriodDurationGreaterThanMin()
    {
        $instance = new CarbonPeriodMinDurationValidator(CarbonInterval::days(2));

        $start = now();
        $end = $start->copy()->addDays(2)->addSecond();
        $this->assertEmpty($instance->validate(CarbonPeriod::create($start, $end)));
    }

    public function testWhenPeriodDurationLessThanMin()
    {
        $instance = new CarbonPeriodMinDurationValidator(CarbonInterval::days(2));

        $start = now();
        $end = $start->copy()->addDays(2)->subSecond();
        $this->assertNotEmpty($errors = $instance->validate(CarbonPeriod::create($start, $end)));
        $this->assertHasError($errors, CarbonPeriodMinDurationValidationError::class);
    }

    public function testWhenNotProvidedCarbonPeriodInstance()
    {
        $instance = new CarbonPeriodMinDurationValidator(CarbonInterval::days(2));

        $this->assertThrows(RestingInternalException::class, function () use ($instance) {
            $instance->validate('');
        });
    }
}