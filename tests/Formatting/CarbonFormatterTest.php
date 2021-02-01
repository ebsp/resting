<?php


namespace Seier\Resting\Tests\Formatting;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Formatting\CarbonFormatter;

class CarbonFormatterTest extends TestCase
{

    private CarbonFormatter $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new CarbonFormatter();
    }

    public function testFormatWhenNull()
    {
        $this->assertNull($this->instance->format(null));
    }

    public function testFormatWithoutCustomFormat()
    {
        $now = now();

        $this->assertEquals($now->toDateTimeString(), $this->instance->format($now));
    }

    public function testFormatWithCustomFormat()
    {
        $format = 'Y-m-d H';
        $now = now();
        $this->instance->withFormat($format);

        $this->assertEquals(
            $now->format($format),
            $this->instance->format($now),
        );
    }
}