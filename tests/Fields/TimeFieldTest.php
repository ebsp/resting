<?php

namespace Seier\Resting\Tests\Fields;

use Seier\Resting\Tests\TestCase;
use Seier\Resting\Fields\TimeField;
use Seier\Resting\Exceptions\InvalidTimeFormatException;

class TimeFieldTest extends TestCase
{
    public function testValidation()
    {
        $field = new TimeField;
        $this->assertEquals($field->validation()[0], 'date_format:"H:i:s"');
    }

    public function testInvalidStringValueValidation()
    {
        $this->expectException(InvalidTimeFormatException::class);

        $field = new TimeField;
        $field->set('No time here');
    }

    public function testInvalidAmericanTimeValueValidation()
    {
        $this->expectException(InvalidTimeFormatException::class);

        $field = new TimeField;
        $field->set('10 am');
    }

    public function testInvalidFormatValueValidation()
    {
        $this->expectException(InvalidTimeFormatException::class);

        $field = new TimeField;
        $field->set('10 00');
    }

    public function testInvalidHourValueValidation()
    {
        $this->expectException(InvalidTimeFormatException::class);

        $field = new TimeField;
        $field->set('24:00:00');
    }

    public function testInvalidMinuteValueValidation()
    {
        $this->expectException(InvalidTimeFormatException::class);

        $field = new TimeField;
        $field->set('00:60:00');
    }

    public function testInvalidSeconds()
    {
        $this->expectException(InvalidTimeFormatException::class);

        $field = new TimeField;
        $field->set('00:00:60');
    }

    public function testValidValus()
    {
        $field = new TimeField;

        for ($hour = 0; $hour <= 23; $hour++) {
            for ($minute = 0; $minute <= 59; $minute++) {
                for ($seconds = 0; $seconds <= 59; $seconds++) {
                    $field->set(implode(':', [$hour, $minute, $seconds]));
                }
            }
        }
        
        $this->assertTrue(true);
    }

    public function testValueCanBeSet()
    {
        $field = new TimeField;
        $field->set($time = '10:01:02');
        $this->assertEquals($time, $field->get());
    }

    public function testValueWithoutSecondsValidation()
    {
        $this->expectException(InvalidTimeFormatException::class);

        $field = new TimeField;
        $field->withSeconds(false);
        $field->set($time = '10:01:02');
        $this->assertEquals($time, $field->get());
    }

    public function testValueCanBeSetWithoutSeconds()
    {
        $field = new TimeField;
        $field->withSeconds(false);
        $field->set($time = '10:01');
        $this->assertEquals($time, $field->get());
    }

    public function testEmptyReturnsNull()
    {
        $field = new TimeField;
        $this->assertNull($field->get());
    }

    public function testNonNullableReturnsNull()
    {
        $field = (new TimeField)->nullable(false);
        $this->assertNull($field->get());
    }
}
