<?php


namespace Seier\Resting\Tests\Fields;


use Carbon\CarbonPeriod;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\Fields\CarbonPeriodField;
use Seier\Resting\Tests\Meta\MockSecondaryValidator;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Tests\Meta\MockSecondaryValidationError;
use Seier\Resting\Validation\Errors\NullableValidationError;

class CarbonPeriodFieldTest extends TestCase
{

    use AssertsErrors;

    private CarbonPeriodField $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new CarbonPeriodField();
    }

    public function testGetCanReturnNull()
    {
        $this->assertNull($this->instance->get());
    }

    public function getGetCanReturnCarbonPeriodInstance()
    {
        $this->instance->set($now = CarbonPeriod::create());
        $this->assertSame($now, $this->instance->get());
    }

    public function testSetCarbonPeriod()
    {
        $from = now();
        $to = $from->copy()->addHour();
        $this->instance->set(CarbonPeriod::create($from, $to));

        $this->assertNotNull($period = $this->instance->get());
        $this->assertEquals($from->unix(), $period->start->unix());
        $this->assertEquals($to->unix(), $period->end->unix());
    }

    public function testSetArray()
    {
        $from = now();
        $to = $from->copy()->addHour();

        $this->instance->set([$from, $to]);
        $this->assertNotNull($period = $this->instance->get());
        $this->assertEquals($from->unix(), $period->start->unix());
        $this->assertEquals($to->unix(), $period->end->unix());
    }

    public function testSetNullWhenNullable()
    {
        $this->instance->nullable();
        $this->instance->set(null);
        $this->assertNull($this->instance->get());
    }

    public function testSetNullWhenNotNullable()
    {
        $this->instance->notNullable();
        $validationException = $this->assertThrowsValidationException(function () {
            $this->instance->set(null);
        });

        $this->assertHasError($validationException, NullableValidationError::class);
    }

    public function testAsArray()
    {
        $period = CarbonPeriod::create(
            $from = now(),
            $to = now()->addDay(),
        );

        $this->instance->set($period);
        $this->assertCount(2, $result = $this->instance->asArray());
        $this->assertEquals($from, $result[0]);
        $this->assertEquals($to, $result[1]);
    }

    public function testAsArrayWhenNull()
    {
        $this->assertCount(2, $result = $this->instance->asArray());
        $this->assertNull($result[0]);
        $this->assertNull($result[1]);
    }

    public function testValidateWithRegisteredSecondaryValidationThatPasses()
    {
        $this->instance->withValidator(MockSecondaryValidator::pass());

        $this->instance->set($period = CarbonPeriod::create(now(), now()->addDay()));
        $this->assertEquals($period, $this->instance->get());
    }

    public function testValidateWithRegisteredSecondaryValidationThatFails()
    {
        $this->instance->withValidator(MockSecondaryValidator::fail());

        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set(CarbonPeriod::create(now(), now()->addDay()));
        });

        $this->assertHasError($exception, MockSecondaryValidationError::class);
    }
}