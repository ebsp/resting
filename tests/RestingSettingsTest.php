<?php

namespace Seier\Resting\Tests;

use Seier\Resting\RestingSettings;
use Seier\Resting\Fields\CarbonGranularity;

class RestingSettingsTest extends TestCase
{
    public function testInstanceReturnsSingleton()
    {
        $a = RestingSettings::instance();
        $b = RestingSettings::instance();

        $this->assertSame($a, $b);
    }

    public function testDefaultsToMutableCarbon()
    {
        $this->assertFalse(RestingSettings::instance()->useImmutableCarbon);
    }

    public function testCanEnableImmutableCarbon()
    {
        RestingSettings::instance()->useImmutableCarbon = true;

        $this->assertTrue(RestingSettings::instance()->useImmutableCarbon);
    }

    public function testResetCreatesNewInstance()
    {
        $before = RestingSettings::instance();
        $before->useImmutableCarbon = true;

        RestingSettings::reset();

        $after = RestingSettings::instance();
        $this->assertNotSame($before, $after);
        $this->assertFalse($after->useImmutableCarbon);
    }

    public function testDefaultCarbonFormats()
    {
        $settings = RestingSettings::instance();

        $this->assertEquals('Y-m-d', $settings->carbonFormat(CarbonGranularity::Date));
        $this->assertEquals('Y-m-d H', $settings->carbonFormat(CarbonGranularity::Hour));
        $this->assertEquals('Y-m-d H:i', $settings->carbonFormat(CarbonGranularity::Minute));
        $this->assertEquals('Y-m-d H:i:s', $settings->carbonFormat(CarbonGranularity::Second));
    }

    public function testCanOverrideCarbonFormat()
    {
        $settings = RestingSettings::instance();
        $settings->setCarbonFormat(CarbonGranularity::Date, 'd/m/Y');

        $this->assertEquals('d/m/Y', $settings->carbonFormat(CarbonGranularity::Date));
    }

    public function testSetCarbonFormatDoesNotAffectOtherGranularities()
    {
        $settings = RestingSettings::instance();
        $settings->setCarbonFormat(CarbonGranularity::Date, 'd/m/Y');

        $this->assertEquals('Y-m-d H:i:s', $settings->carbonFormat(CarbonGranularity::Second));
    }

    public function testResetRestoresDefaultCarbonFormats()
    {
        RestingSettings::instance()->setCarbonFormat(CarbonGranularity::Date, 'd/m/Y');

        RestingSettings::reset();

        $this->assertEquals('Y-m-d', RestingSettings::instance()->carbonFormat(CarbonGranularity::Date));
    }
}
