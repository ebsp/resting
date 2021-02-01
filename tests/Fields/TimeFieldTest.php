<?php

namespace Seier\Resting\Tests\Fields;

use Seier\Resting\Fields\Time;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\Fields\TimeField;
use Seier\Resting\Parsing\TimeParseError;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Tests\Meta\MockSecondaryValidator;
use Seier\Resting\Tests\Meta\MockSecondaryValidationError;
use Seier\Resting\Validation\Errors\NullableValidationError;

class TimeFieldTest extends TestCase
{

    use AssertsErrors;

    private TimeField $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new TimeField();
    }

    public function testGetEmptyReturnsNull()
    {
        $this->assertNull($this->instance->get());
    }

    public function testIsNullReturnsTrue()
    {
        $this->assertTrue($this->instance->isNull());
    }

    public function testSetTimeObject()
    {
        $this->instance->set(new Time(hours: 20, minutes: 10, seconds: 30));

        $result = $this->instance->get();
        $this->assertEquals(20, $result->hours);
        $this->assertEquals(10, $result->minutes);
        $this->assertEquals(30, $result->seconds);
    }

    public function testSetCarbon()
    {
        $this->instance->set(now()->hours(10)->minutes(20)->seconds(30));

        $result = $this->instance->get();
        $this->assertEquals(10, $result->hours);
        $this->assertEquals(20, $result->minutes);
        $this->assertEquals(30, $result->seconds);
    }

    public function testSetCanParseStringWithHoursAndMinutes()
    {
        $this->instance->set('20:30');

        $result = $this->instance->get();
        $this->assertEquals(20, $result->hours);
        $this->assertEquals(30, $result->minutes);
        $this->assertEquals(0, $result->seconds);
    }

    public function testSetCanParseStringWithHoursMinutesAndSeconds()
    {
        $this->instance->set('20:30:15');

        $result = $this->instance->get();
        $this->assertEquals(20, $result->hours);
        $this->assertEquals(30, $result->minutes);
        $this->assertEquals(15, $result->seconds);
    }

    public function testNullableSetWhenGivenNull()
    {
        $this->instance->nullable();

        $this->instance->set(null);
        $this->assertNull($this->instance->get());
    }

    public function testNonNullableSetWhenGivenNull()
    {
        $this->instance->nullable(false);

        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set(null);
        });

        $this->assertCount(1, $exception->getErrors());
        $this->assertHasError($exception, NullableValidationError::class, '');
    }

    public function testSetThrowsWhenProvidedInvalidFormat()
    {
        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set('a:b:c');
        });

        $this->assertCount(1, $exception->getErrors());
        $this->assertHasError($exception, TimeParseError::class, '');
    }

    public function testSetThrowsWhenSecondsRequiredButNotProvided()
    {
        $this->instance->requireSeconds();

        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set('10:20');
        });

        $this->assertCount(1, $exception->getErrors());
        $this->assertHasError($exception, TimeParseError::class, '');
    }

    public function testFormattedWithoutCustomFormat()
    {
        $this->instance->set($time = new Time(
            hours: 5,
            minutes: 6,
            seconds: 19
        ));

        $this->assertEquals(
            '05:06:19',
            $this->instance->formatted(),
        );
    }

    public function testFormattedWithCustomFormat()
    {
        $this->instance->getFormatter()->withFormat('H:s');
        $this->instance->set($time = new Time(
            hours: 5,
            minutes: 6,
            seconds: 19
        ));

        $this->assertEquals(
            '05:19',
            $this->instance->formatted(),
        );
    }

    public function testValidateWithRegisteredSecondaryValidationThatPasses()
    {
        $this->instance->withValidator(MockSecondaryValidator::pass());

        $this->instance->set($time = Time::zeroes());
        $this->assertSame($time, $this->instance->get());
    }

    public function testValidateWithRegisteredSecondaryValidationThatFails()
    {
        $this->instance->withValidator(MockSecondaryValidator::fail());

        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set(Time::zeroes());
        });

        $this->assertHasError($exception, MockSecondaryValidationError::class);
    }

    public function testCanCastEmptyValuesToNull()
    {
        $this->instance->emptyStringAsNull();

        $this->instance->set('');
        $this->assertNull($this->instance->get());
    }
}
