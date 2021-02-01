<?php


namespace Seier\Resting\Tests\Formatting;


use Seier\Resting\Fields\Time;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\Formatting\TimeFormatter;

class TimeFormatterTest extends TestCase
{

    private TimeFormatter $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new TimeFormatter();
    }

    public function testFormatWhenNull()
    {
        $this->assertNull($this->instance->format(null));
    }

    public function testFormatWithoutCustomFormat()
    {
        $time = new Time(
            hours: 4,
            minutes: 28,
            seconds: 10
        );

        $this->assertEquals('04:28:10', $this->instance->format($time));
    }

    public function testFormatWithCustomFormat()
    {
        $time = new Time(
            hours: 4,
            minutes: 28,
            seconds: 10
        );

        $this->instance->withFormat('H:s');

        $this->assertEquals('04:10', $this->instance->format($time));

    }
}