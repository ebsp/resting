<?php


namespace Seier\Resting\Tests\Formatting;


use Carbon\CarbonImmutable;
use Seier\Resting\RestingSettings;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\Formatting\CarbonFormatter;
use Seier\Resting\Fields\CarbonGranularity;

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

    public function testFormatCarbonImmutable()
    {
        $now = CarbonImmutable::now();

        $this->assertEquals($now->toDateTimeString(), $this->instance->format($now));
    }

    public function testFormatUsesGranularityFormat()
    {
        $now = now();
        $this->instance->withGranularity(CarbonGranularity::Date);

        $this->assertEquals($now->format('Y-m-d'), $this->instance->format($now));
    }

    public function testFormatUsesConfiguredGranularityFormat()
    {
        RestingSettings::instance()->setCarbonFormat(CarbonGranularity::Hour, 'H:00');

        $now = now();
        $this->instance->withGranularity(CarbonGranularity::Hour);

        $this->assertEquals($now->format('H:00'), $this->instance->format($now));
    }

    public function testCustomFormatOverridesGranularityFormat()
    {
        $now = now();
        $this->instance->withGranularity(CarbonGranularity::Date);
        $this->instance->withFormat('d/m/Y');

        $this->assertEquals($now->format('d/m/Y'), $this->instance->format($now));
    }
}