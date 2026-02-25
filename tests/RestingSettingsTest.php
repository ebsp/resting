<?php

namespace Seier\Resting\Tests;

use Seier\Resting\RestingSettings;

class RestingSettingsTest extends TestCase
{
    protected function tearDown(): void
    {
        RestingSettings::reset();

        parent::tearDown();
    }

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
}
