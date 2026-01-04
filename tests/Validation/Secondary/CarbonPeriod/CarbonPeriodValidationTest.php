<?php


namespace Seier\Resting\Tests\Validation\Secondary\CarbonPeriod;


use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Carbon\CarbonInterval;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Tests\Meta\MockPrimaryValidator;
use Seier\Resting\Validation\Secondary\CarbonPeriod\CarbonPeriodMinDurationValidationError;
use Seier\Resting\Validation\Secondary\CarbonPeriod\CarbonPeriodMaxDurationValidationError;

class CarbonPeriodValidationTest extends TestCase
{

    use AssertsErrors;

    private MockPrimaryValidator $validator;
    private CarbonPeriodValidationTestBench $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->validator = new MockPrimaryValidator();
        $this->instance = new CarbonPeriodValidationTestBench($this->validator);
    }

    private function period(Carbon $from, Carbon $to): CarbonPeriod
    {
        return CarbonPeriod::create($from, $to);
    }

    public function testMinHoursWhenPasses()
    {
        $this->instance->minHours(1);

        $from = now();
        $to = $from->copy()->addHour();
        $period = $this->period($from, $to);

        $this->assertEmpty($this->validator->validate($period));
    }

    public function testMinHoursWhenFails()
    {
        $this->instance->minHours(1);

        $from = now();
        $to = $from->copy()->addHour()->subSecond();
        $period = $this->period($from, $to);

        $this->assertNotEmpty($errors = $this->validator->validate($period));
        $this->assertHasError($errors, CarbonPeriodMinDurationValidationError::class);
    }

    public function testMaxHoursWhenPasses()
    {
        $this->instance->maxHours(1);

        $from = now();
        $to = $from->copy()->addHour();
        $period = $this->period($from, $to);

        $this->assertEmpty($this->validator->validate($period));
    }

    public function testMaxHoursWhenFails()
    {
        $this->instance->maxHours(1);

        $from = now();
        $to = $from->copy()->addHour()->addSecond();
        $period = $this->period($from, $to);

        $this->assertNotEmpty($errors = $this->validator->validate($period));
        $this->assertHasError($errors, CarbonPeriodMaxDurationValidationError::class);
    }

    public function testMinDaysWhenPasses()
    {
        $this->instance->minDays(1);

        $from = now();
        $to = $from->copy()->addDay();
        $period = $this->period($from, $to);

        $this->assertEmpty($this->validator->validate($period));
    }

    public function testMinDaysWhenFails()
    {
        $this->instance->minDays(1);

        $from = now();
        $to = $from->copy()->addDay()->subSecond();
        $period = $this->period($from, $to);

        $this->assertNotEmpty($errors = $this->validator->validate($period));
        $this->assertHasError($errors, CarbonPeriodMinDurationValidationError::class);
    }

    public function testMaxDaysWhenPasses()
    {
        $this->instance->maxDays(1);

        $from = now();
        $to = $from->copy()->addDay();
        $period = $this->period($from, $to);

        $this->assertEmpty($this->validator->validate($period));
    }

    public function testMaxDaysWhenFails()
    {
        $this->instance->maxDays(1);

        $from = now();
        $to = $from->copy()->addDay()->addSecond();
        $period = $this->period($from, $to);

        $this->assertNotEmpty($errors = $this->validator->validate($period));
        $this->assertHasError($errors, CarbonPeriodMaxDurationValidationError::class);
    }

    public function testMinWeeksWhenPasses()
    {
        $this->instance->minWeeks(1);

        $from = now();
        $to = $from->copy()->addWeek();
        $period = $this->period($from, $to);

        $this->assertEmpty($this->validator->validate($period));
    }

    public function testMinWeeksWhenFails()
    {
        $this->instance->minWeeks(1);

        $from = now();
        $to = $from->copy()->addWeek()->subSecond();
        $period = $this->period($from, $to);

        $this->assertNotEmpty($errors = $this->validator->validate($period));
        $this->assertHasError($errors, CarbonPeriodMinDurationValidationError::class);
    }

    public function testMaxWeeksWhenPasses()
    {
        $this->instance->maxWeeks(1);

        $from = now();
        $to = $from->copy()->addWeek();
        $period = $this->period($from, $to);

        $this->assertEmpty($this->validator->validate($period));
    }

    public function testMaxWeeksWhenFails()
    {
        $this->instance->maxWeeks(1);

        $from = now();
        $to = $from->copy()->addWeek()->addSecond();
        $period = $this->period($from, $to);

        $this->assertNotEmpty($errors = $this->validator->validate($period));
        $this->assertHasError($errors, CarbonPeriodMaxDurationValidationError::class);
    }

    public function testMinIntervalWhenPasses()
    {
        $this->instance->minInterval(CarbonInterval::minute());

        $from = now();
        $to = $from->copy()->addMinute();
        $period = $this->period($from, $to);

        $this->assertEmpty($this->validator->validate($period));
    }

    public function testMinIntervalWhenFails()
    {
        $this->instance->minInterval(CarbonInterval::minute());

        $from = now();
        $to = $from->copy()->addMinute()->subSecond();
        $period = $this->period($from, $to);

        $this->assertNotEmpty($errors = $this->validator->validate($period));
        $this->assertHasError($errors, CarbonPeriodMinDurationValidationError::class);
    }

    public function testMaxIntervalWhenPasses()
    {
        $this->instance->maxInterval(CarbonInterval::minute());

        $from = now();
        $to = $from->copy()->addMinute();
        $period = $this->period($from, $to);

        $this->assertEmpty($this->validator->validate($period));
    }

    public function testMaxIntervalWhenFails()
    {
        $this->instance->maxInterval(CarbonInterval::minute());

        $from = now();
        $to = $from->copy()->addMinute()->addSecond();
        $period = $this->period($from, $to);

        $this->assertNotEmpty($errors = $this->validator->validate($period));
        $this->assertHasError($errors, CarbonPeriodMaxDurationValidationError::class);
    }
}