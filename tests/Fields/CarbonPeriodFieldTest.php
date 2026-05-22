<?php


namespace Seier\Resting\Tests\Fields;


use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\Fields\CarbonPeriodField;
use Seier\Resting\Fields\CarbonGranularity;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Tests\Meta\MockSecondaryValidator;
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
        $from = now()->startOfSecond();
        $to = $from->copy()->addDay();
        $period = CarbonPeriod::create($from, $to);

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

    public function testGetWhenUseStartWhenEndMissingTrue()
    {
        $period = CarbonPeriod::create($start = now());

        $this->instance->useStartWhenEndIsMissing();
        $this->instance->set($period);

        $this->assertEquals(
            $start->unix(),
            $this->instance->get()->end->unix(),
        );
    }

    public function testAsArrayWhenUseStartWhenEndMissingTrue()
    {
        $period = CarbonPeriod::create(now());

        $this->instance->useStartWhenEndIsMissing();
        $this->instance->set($period);

        [$start, $end] = $this->instance->asArray();

        $this->assertEquals(
            $start->unix(),
            $end->unix(),
        );
    }

    public function testEndWhenUseStartWhenEndMissingTrue()
    {
        $period = CarbonPeriod::create($start = now());

        $this->instance->useStartWhenEndIsMissing();
        $this->instance->set($period);

        $this->assertEquals(
            $start->unix(),
            $this->instance->end()->unix(),
        );
    }

    public function testValidateWithRegisteredSecondaryValidationThatPasses()
    {
        $this->instance->withValidator(MockSecondaryValidator::pass());

        $start = now()->startOfSecond();
        $this->instance->set($period = CarbonPeriod::create($start, $start->copy()->addDay()));
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

    public function testSetArrayOfCarbonImmutable()
    {
        $from = CarbonImmutable::now();
        $to = $from->addHour();

        $this->instance->set([$from, $to]);
        $this->assertNotNull($period = $this->instance->get());
        $this->assertEquals($from->unix(), $period->start->unix());
        $this->assertEquals($to->unix(), $period->end->unix());
    }

    public function testStartReturnsCarbonInstance()
    {
        $period = CarbonPeriod::create(Carbon::now(), Carbon::now()->addDay());
        $this->instance->set($period);
        $this->assertInstanceOf(Carbon::class, $this->instance->start());
    }

    public function testStartReturnsCarbonInstanceWhenCreatedWithImmutable()
    {
        $period = CarbonPeriod::create(CarbonImmutable::now(), CarbonImmutable::now()->addDay());

        $this->instance->set($period);
        $this->assertInstanceOf(Carbon::class, $this->instance->start());
    }

    public function testEndReturnsCarbonInstance()
    {
        $period = CarbonPeriod::create(Carbon::now(), Carbon::now()->addDay());
        $this->instance->set($period);
        $this->assertInstanceOf(Carbon::class, $this->instance->end());
    }

    public function testEndReturnsCarbonInstanceWhenCreatedWithImmutable()
    {
        $period = CarbonPeriod::create(CarbonImmutable::now(), CarbonImmutable::now()->addDay());

        $this->instance->set($period);
        $this->assertInstanceOf(Carbon::class, $this->instance->end());
    }

    public function testDefaultGranularityTruncatesToSecond()
    {
        $start = Carbon::create(2025, 1, 2, 3, 4, 5)->addMicroseconds(123456);
        $end = Carbon::create(2025, 1, 3, 6, 7, 8)->addMicroseconds(654321);

        $this->instance->set(CarbonPeriod::create($start, $end));

        $period = $this->instance->get();
        $this->assertEquals(Carbon::create(2025, 1, 2, 3, 4, 5), $period->start);
        $this->assertEquals(Carbon::create(2025, 1, 3, 6, 7, 8), $period->end);
    }

    public function testGranularityDateTruncatesBothEndpoints()
    {
        $this->instance->granularity(CarbonGranularity::Date);
        $this->instance->set(CarbonPeriod::create(
            Carbon::create(2025, 1, 2, 3, 4, 5),
            Carbon::create(2025, 1, 3, 6, 7, 8),
        ));

        $period = $this->instance->get();
        $this->assertEquals(Carbon::create(2025, 1, 2, 0, 0, 0), $period->start);
        $this->assertEquals(Carbon::create(2025, 1, 3, 0, 0, 0), $period->end);
    }

    public function testGranularityHourTruncatesBothEndpoints()
    {
        $this->instance->granularity(CarbonGranularity::Hour);
        $this->instance->set(CarbonPeriod::create(
            Carbon::create(2025, 1, 2, 3, 4, 5),
            Carbon::create(2025, 1, 3, 6, 7, 8),
        ));

        $period = $this->instance->get();
        $this->assertEquals(Carbon::create(2025, 1, 2, 3, 0, 0), $period->start);
        $this->assertEquals(Carbon::create(2025, 1, 3, 6, 0, 0), $period->end);
    }

    public function testGranularityMinuteTruncatesBothEndpoints()
    {
        $this->instance->granularity(CarbonGranularity::Minute);
        $this->instance->set(CarbonPeriod::create(
            Carbon::create(2025, 1, 2, 3, 4, 5),
            Carbon::create(2025, 1, 3, 6, 7, 8),
        ));

        $period = $this->instance->get();
        $this->assertEquals(Carbon::create(2025, 1, 2, 3, 4, 0), $period->start);
        $this->assertEquals(Carbon::create(2025, 1, 3, 6, 7, 0), $period->end);
    }

    public function testGranularityTruncatesArrayInput()
    {
        $this->instance->granularity(CarbonGranularity::Date);
        $this->instance->set([
            Carbon::create(2025, 1, 2, 3, 4, 5),
            Carbon::create(2025, 1, 3, 6, 7, 8),
        ]);

        $period = $this->instance->get();
        $this->assertEquals(Carbon::create(2025, 1, 2), $period->start);
        $this->assertEquals(Carbon::create(2025, 1, 3), $period->end);
    }

    public function testGranularityTruncatesParsedStringInput()
    {
        $this->instance->granularity(CarbonGranularity::Minute);
        $this->instance->set(['2025-01-02 03:04:05', '2025-01-03 06:07:08']);

        $period = $this->instance->get();
        $this->assertEquals(Carbon::create(2025, 1, 2, 3, 4, 0), $period->start);
        $this->assertEquals(Carbon::create(2025, 1, 3, 6, 7, 0), $period->end);
    }

    public function testGranularityTruncatesPeriodWithoutEnd()
    {
        $this->instance->endNotRequired();
        $this->instance->granularity(CarbonGranularity::Date);
        $this->instance->set(CarbonPeriod::create(Carbon::create(2025, 1, 2, 3, 4, 5)));

        $period = $this->instance->get();
        $this->assertEquals(Carbon::create(2025, 1, 2, 0, 0, 0), $period->start);
        $this->assertNull($period->end);
    }

    public function testGranularityTruncatesCarbonImmutablePeriod()
    {
        $this->instance->granularity(CarbonGranularity::Date);
        $this->instance->set(CarbonPeriod::create(
            CarbonImmutable::create(2025, 1, 2, 3, 4, 5),
            CarbonImmutable::create(2025, 1, 3, 6, 7, 8),
        ));

        $period = $this->instance->get();
        $this->assertEquals(Carbon::create(2025, 1, 2, 0, 0, 0), $period->start);
        $this->assertEquals(Carbon::create(2025, 1, 3, 0, 0, 0), $period->end);
    }

    public function testGranularityDoesNotMutateProvidedPeriod()
    {
        $period = CarbonPeriod::create(
            Carbon::create(2025, 1, 2, 3, 4, 5),
            Carbon::create(2025, 1, 3, 6, 7, 8),
        );

        $this->instance->granularity(CarbonGranularity::Date);
        $this->instance->set($period);

        $this->assertEquals(Carbon::create(2025, 1, 2, 3, 4, 5), $period->start);
        $this->assertEquals(Carbon::create(2025, 1, 3, 6, 7, 8), $period->end);
    }
}