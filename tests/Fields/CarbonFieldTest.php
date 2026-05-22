<?php

namespace Seier\Resting\Tests\Fields;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Seier\Resting\RestingSettings;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\Fields\CarbonField;
use Seier\Resting\Fields\CarbonGranularity;
use Seier\Resting\Parsing\CarbonParseError;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Tests\Meta\MockSecondaryValidator;
use Seier\Resting\Tests\Meta\MockSecondaryValidationError;
use Seier\Resting\Validation\Errors\NullableValidationError;
use Seier\Resting\Validation\Errors\NotCarbonValidationError;
use Seier\Resting\Validation\Secondary\Comparable\MinValidationError;
use Seier\Resting\Validation\Secondary\Carbon\CarbonMinValidationError;

class CarbonFieldTest extends TestCase
{

    use AssertsErrors;

    private CarbonField $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new CarbonField();
    }

    public function testGetCanReturnNull()
    {
        $this->assertNull($this->instance->get());
    }

    public function testGetCanReturnCarbonInstance()
    {
        $this->instance->set($now = now());
        $this->assertInstanceOf(Carbon::class, $this->instance->get());
        $this->assertEquals($now->copy()->startOfSecond(), $this->instance->get());
    }

    public function testGetReturnsNewInstanceEveryTime()
    {
        $this->instance->set($now = now());
        $this->assertNotSame($now, $this->instance->get());
    }

    public function testSetCarbon()
    {
        $now = now();
        $this->instance->set($now);
        $this->assertEquals($now->copy()->startOfSecond(), $this->instance->get());
    }

    public function testSetDoesNotMutateProvidedCarbon()
    {
        $now = now();
        $micro = $now->micro;
        $this->instance->set($now);
        $this->assertSame($micro, $now->micro);
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

    public function testSetThrowsWhenProvidedWrongType()
    {
        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set(1);
        });

        $this->assertHasError($exception, NotCarbonValidationError::class);
    }

    public function testSetThrowsWhenCarbonFailsValidation()
    {
        $this->instance->min($limit = now());
        $validationException = $this->assertThrowsValidationException(function () use ($limit) {
            $this->instance->set($limit->copy()->subSecond());
        });

        $this->assertCount(1, $validationException->getErrors());
        $this->assertHasError($validationException, MinValidationError::class);
    }

    public function testSetDoesNotThrowWhenCarbonPassesValidation()
    {
        $this->instance->min($limit = now());
        $this->assertDoesNotThrowValidationException(function () use ($limit) {
            $this->instance->set($limit->copy()->addSecond());
        });
    }

    public function testSetCanParseDateString()
    {
        $now = now()->startOfDay();

        $this->instance->set($now->toDateString());
        $this->assertEquals($now, $this->instance->get());
    }

    public function testSetCanParseDatetimeString()
    {
        $now = now()->startOfSecond();

        $this->instance->set($now->toDateTimeString());
        $this->assertEquals($now, $this->instance->get());
    }

    public function testSetWhenProvidedNonParsableString()
    {
        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set('invalid');
        });

        $this->assertHasError($exception, CarbonParseError::class);
    }

    public function testFormattedWithoutCustomFormat()
    {
        $this->instance->set($now = now());

        $this->assertEquals(
            $now->toDateTimeString(),
            $this->instance->formatted(),
        );
    }

    public function testFormattedWithCustomFormat()
    {
        $this->instance->set($now = now());
        $this->instance->getFormatter()->withFormat($format = 'Y-m-d');

        $this->assertEquals(
            $now->format($format),
            $this->instance->formatted(),
        );
    }

    public function testValidateWithRegisteredSecondaryValidationThatPasses()
    {
        $this->instance->withValidator(MockSecondaryValidator::pass());

        $this->instance->set($now = now());
        $this->assertEquals($now->copy()->startOfSecond(), $this->instance->get());
    }

    public function testValidateWithRegisteredSecondaryValidationThatFails()
    {
        $this->instance->withValidator(MockSecondaryValidator::fail());

        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set(now());
        });

        $this->assertHasError($exception, MockSecondaryValidationError::class);
    }

    public function testCanCastEmptyValuesToNull()
    {
        $this->instance->emptyStringAsNull();

        $this->instance->set('');
        $this->assertNull($this->instance->get());
    }

    public function testSetCarbonImmutableReturnsCarbonImmutable()
    {
        $now = CarbonImmutable::now();
        $this->instance->set($now);
        $this->assertInstanceOf(CarbonImmutable::class, $this->instance->get());
    }

    public function testSetCarbonImmutable()
    {
        $now = CarbonImmutable::now();
        $this->instance->set($now);
        $this->assertEquals($now->startOfSecond(), $this->instance->get());
    }

    public function testSetCarbonImmutableReturnsNewInstance()
    {
        $now = CarbonImmutable::now();
        $this->instance->set($now);
        $this->assertNotSame($now, $this->instance->get());
    }

    public function testSetCarbonImmutableWithMinValidation()
    {
        $this->instance->min($limit = CarbonImmutable::now());
        $this->assertDoesNotThrowValidationException(function () use ($limit) {
            $this->instance->set($limit->addSecond());
        });
    }

    public function testSetCarbonImmutableFailsMinValidation()
    {
        $this->instance->min($limit = CarbonImmutable::now());
        $validationException = $this->assertThrowsValidationException(function () use ($limit) {
            $this->instance->set($limit->subSecond());
        });

        $this->assertCount(1, $validationException->getErrors());
        $this->assertHasError($validationException, MinValidationError::class);
    }

    public function testFormattedWithCarbonImmutable()
    {
        $now = CarbonImmutable::now();
        $this->instance->set($now);

        $this->assertEquals(
            $now->toDateTimeString(),
            $this->instance->formatted(),
        );
    }

    public function testDefaultGranularityTruncatesToSecond()
    {
        $value = Carbon::create(2025, 1, 2, 3, 4, 5)->addMicroseconds(123456);
        $this->instance->set($value);

        $this->assertEquals(Carbon::create(2025, 1, 2, 3, 4, 5), $this->instance->get());
    }

    public function testGranularityDateTruncatesToStartOfDay()
    {
        $this->instance->granularity(CarbonGranularity::Date);
        $this->instance->set(Carbon::create(2025, 1, 2, 3, 4, 5));

        $this->assertEquals(Carbon::create(2025, 1, 2, 0, 0, 0), $this->instance->get());
    }

    public function testGranularityHourTruncatesToStartOfHour()
    {
        $this->instance->granularity(CarbonGranularity::Hour);
        $this->instance->set(Carbon::create(2025, 1, 2, 3, 4, 5));

        $this->assertEquals(Carbon::create(2025, 1, 2, 3, 0, 0), $this->instance->get());
    }

    public function testGranularityMinuteTruncatesToStartOfMinute()
    {
        $this->instance->granularity(CarbonGranularity::Minute);
        $this->instance->set(Carbon::create(2025, 1, 2, 3, 4, 5));

        $this->assertEquals(Carbon::create(2025, 1, 2, 3, 4, 0), $this->instance->get());
    }

    public function testGranularityTruncatesParsedString()
    {
        $this->instance->granularity(CarbonGranularity::Minute);
        $this->instance->set('2025-01-02 03:04:05');

        $this->assertEquals(Carbon::create(2025, 1, 2, 3, 4, 0), $this->instance->get());
    }

    public function testAcceptsAnyCarbonParseableString()
    {
        $this->instance->granularity(CarbonGranularity::Date);
        $this->instance->set('2025-01-02T03:04:05+00:00');

        $this->assertEquals(Carbon::create(2025, 1, 2, 0, 0, 0), $this->instance->get());
    }

    public function testFormattedUsesGranularityFormat()
    {
        $this->instance->granularity(CarbonGranularity::Date);
        $this->instance->set(Carbon::create(2025, 1, 2, 3, 4, 5));

        $this->assertEquals('2025-01-02', $this->instance->formatted());
    }

    public function testFormattedUsesGranularityFormatForMinute()
    {
        $this->instance->granularity(CarbonGranularity::Minute);
        $this->instance->set(Carbon::create(2025, 1, 2, 3, 4, 5));

        $this->assertEquals('2025-01-02 03:04', $this->instance->formatted());
    }

    public function testFormattedUsesConfiguredGranularityFormat()
    {
        RestingSettings::instance()->setCarbonFormat(CarbonGranularity::Minute, 'H:i');

        $this->instance->granularity(CarbonGranularity::Minute);
        $this->instance->set(Carbon::create(2025, 1, 2, 3, 4, 5));

        $this->assertEquals('03:04', $this->instance->formatted());
    }

    public function testWithFormatOverridesGranularityFormat()
    {
        $this->instance->granularity(CarbonGranularity::Date);
        $this->instance->withFormat('d/m/Y');
        $this->instance->set(Carbon::create(2025, 1, 2, 3, 4, 5));

        $this->assertEquals('02/01/2025', $this->instance->formatted());
    }
}
