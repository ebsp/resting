<?php


namespace Seier\Resting\Tests\Validation;


use Carbon\CarbonPeriod;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\Validation\CarbonValidator;
use Seier\Resting\Validation\CarbonPeriodValidator;
use Seier\Resting\Tests\Meta\MockSecondaryValidator;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Tests\Meta\MockSecondaryValidationError;
use Seier\Resting\Validation\Errors\NotCarbonPeriodValidationError;
use Seier\Resting\Validation\Secondary\Comparable\MinValidationError;
use Seier\Resting\Validation\Secondary\Comparable\MaxValidationError;
use Seier\Resting\Validation\Errors\CarbonPeriodEndRequiredValidationError;
use Seier\Resting\Validation\Errors\CarbonPeriodOrderedRequiredValidationError;

class CarbonPeriodValidatorTest extends TestCase
{

    use AssertsErrors;

    private CarbonPeriodValidator $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new CarbonPeriodValidator();
    }

    public function testValidateClosedCarbonPeriod()
    {
        $this->assertEmpty($this->instance->validate(
            CarbonPeriod::create(now(), now()->addDay())
        ));
    }

    public function testValidateOpenCarbonPeriodWhenDefault()
    {
        $this->assertNotEmpty($errors = $this->instance->validate(
            CarbonPeriod::create(now())
        ));

        $this->assertHasError($errors, CarbonPeriodEndRequiredValidationError::class);
    }

    public function testValidateOpenCarbonPeriodWhenEndNotRequired()
    {
        $this->instance->requireEnd(false);

        $this->assertEmpty($this->instance->validate(
            CarbonPeriod::create(now())
        ));
    }

    public function testValidateOpenCarbonPeriodWhenEndRequired()
    {
        $this->instance->requireEnd(true);

        $this->assertNotEmpty($errors = $this->instance->validate(
            CarbonPeriod::create(now())
        ));

        $this->assertHasError($errors, CarbonPeriodEndRequiredValidationError::class);
    }

    public function testValidationOrderedWhenDefault()
    {
        $this->assertNotEmpty($errors = $this->instance->validate(
            CarbonPeriod::create(now(), now()->subSecond())
        ));

        $this->assertHasError($errors, CarbonPeriodOrderedRequiredValidationError::class);
    }

    public function testValidationOrderedWhenOrderRequired()
    {
        $this->instance->requireOrdered(true);

        $this->assertNotEmpty($errors = $this->instance->validate(
            CarbonPeriod::create(now(), now()->subSecond())
        ));

        $this->assertHasError($errors, CarbonPeriodOrderedRequiredValidationError::class);
    }

    public function testValidationOrderedWhenOrderNotRequired()
    {
        $this->instance->requireOrdered(false);

        $this->assertEmpty($this->instance->validate(
            CarbonPeriod::create(now(), now()->subSecond())
        ));
    }

    public function testValidationOrderedWhenEquals()
    {
        $now = now();
        $this->assertEmpty($this->instance->validate(
            CarbonPeriod::create($now, $now)
        ));
    }

    public function testValidateIncorrectType()
    {
        $this->assertNotEmpty($errors = $this->instance->validate(''));
        $this->assertHasError($errors, NotCarbonPeriodValidationError::class);
    }


    public function testValidateNullW()
    {
        $this->assertNotEmpty($errors = $this->instance->validate(null));
        $this->assertHasError($errors, NotCarbonPeriodValidationError::class);
    }

    public function testValidateWithStartValidatorThatPasses()
    {
        $now = now();
        $this->instance->onStart(function (CarbonValidator $start) use ($now) {
            $start->min($now);
        });

        $this->assertEmpty($this->instance->validate(
            CarbonPeriod::create($now, $now->copy()->addDay())
        ));
    }

    public function testValidateWithStartValidatorThatFails()
    {
        $now = now();
        $this->instance->onStart(function (CarbonValidator $start) use ($now) {
            $start->min($now);
        });

        $this->assertNotEmpty($errors = $this->instance->validate(
            CarbonPeriod::create($now->copy()->subDay(), $now->copy()->addDay())
        ));

        $this->assertHasError($errors, MinValidationError::class, 'start');
    }

    public function testValidateWithEndValidatorThatPasses()
    {
        $now = now();
        $this->instance->onEnd(function (CarbonValidator $end) use ($now) {
            $end->max($now);
        });

        $this->assertEmpty($this->instance->validate(
            CarbonPeriod::create($now->copy()->subDay(), $now)
        ));
    }

    public function testValidateWithEndValidatorThatFails()
    {
        $now = now();
        $this->instance->onEnd(function (CarbonValidator $end) use ($now) {
            $end->max($now);
        });

        $this->assertNotEmpty($errors = $this->instance->validate(
            CarbonPeriod::create($now->copy()->subDay(), $now->copy()->addSecond())
        ));

        $this->assertHasError($errors, MaxValidationError::class, 'end');
    }

    public function testValidateDoesNotRunEndValidatorWhenOpenEnded()
    {
        $now = now();
        $this->instance->requireEnd(false);
        $this->instance->onEnd(function (CarbonValidator $end) use ($now) {
            $end->max($now);
        });

        $this->assertEmpty($this->instance->validate(
            CarbonPeriod::create($now)
        ));
    }

    public function testValidateWithRegisteredSecondaryValidationThatPasses()
    {
        $this->instance->withValidator(MockSecondaryValidator::pass());

        $this->assertEmpty($this->instance->validate(CarbonPeriod::create(
            now(), now()->addDay()
        )));
    }

    public function testValidateWithRegisteredSecondaryValidationThatFails()
    {
        $this->instance->withValidator(MockSecondaryValidator::fail());

        $this->assertNotEmpty($errors = $this->instance->validate(CarbonPeriod::create(
            now(), now()->addDay()
        )));

        $this->assertHasError($errors, MockSecondaryValidationError::class);
    }
}