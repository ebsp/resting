<?php


namespace Seier\Resting\Tests\Validation\Secondary\CarbonPeriod;


use Carbon\CarbonPeriod;
use Carbon\CarbonInterval;
use Seier\Resting\Tests\TestCase;
use Jchook\AssertThrows\AssertThrows;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Exceptions\RestingInternalException;
use Seier\Resting\Validation\Secondary\CarbonPeriod\CarbonPeriodMaxDurationValidator;
use Seier\Resting\Validation\Secondary\CarbonPeriod\CarbonPeriodMaxDurationValidationError;

class CarbonPeriodMaxDurationValidatorTest extends TestCase
{

    use AssertThrows;
    use AssertsErrors;

    public function testWhenPeriodDurationEqualsMax()
    {
        $instance = new CarbonPeriodMaxDurationValidator(CarbonInterval::days(2));

        $start = now();
        $end = $start->copy()->addDays(2);
        $this->assertEmpty($instance->validate(CarbonPeriod::create($start, $end)));
    }

    public function testWhenPeriodDurationGreaterThanMax()
    {
        $instance = new CarbonPeriodMaxDurationValidator(CarbonInterval::days(2));

        $start = now();
        $end = $start->copy()->addDays(2)->addSecond();
        $this->assertNotEmpty($errors = $instance->validate(CarbonPeriod::create($start, $end)));
        $this->assertHasError($errors, CarbonPeriodMaxDurationValidationError::class);
    }

    public function testWhenPeriodDurationLessThanMax()
    {
        $instance = new CarbonPeriodMaxDurationValidator(CarbonInterval::days(2));

        $start = now();
        $end = $start->copy()->addDays(2)->subSecond();
        $this->assertEmpty($instance->validate(CarbonPeriod::create($start, $end)));
    }

    public function testWhenPeriodDoesNotHaveEndWhenNotAllowed()
    {
        $instance = new CarbonPeriodMaxDurationValidator(CarbonInterval::days(2), allowWithoutEnd: false);

        $this->assertNotEmpty($errors = $instance->validate(CarbonPeriod::create(now(), end: null)));
        $this->assertHasError($errors, CarbonPeriodMaxDurationValidationError::class);
    }

    public function testWhenPeriodDoesNotHaveEndWhenAllowed()
    {
        $instance = new CarbonPeriodMaxDurationValidator(CarbonInterval::days(2), allowWithoutEnd: true);

        $this->assertEmpty($instance->validate(CarbonPeriod::create(now(), end: null)));
    }

    public function testWhenNotProvidedCarbonPeriodInstance()
    {
        $instance = new CarbonPeriodMaxDurationValidator(CarbonInterval::days(2));

        $this->assertThrows(RestingInternalException::class, function () use ($instance) {
            $instance->validate('');
        });
    }
}