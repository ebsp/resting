<?php

namespace Seier\Resting\Tests\Fields;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\Fields\CarbonGranularity;

class CarbonGranularityTest extends TestCase
{
    public function testCases()
    {
        $this->assertEquals('date', CarbonGranularity::Date->value);
        $this->assertEquals('hour', CarbonGranularity::Hour->value);
        $this->assertEquals('minute', CarbonGranularity::Minute->value);
        $this->assertEquals('second', CarbonGranularity::Second->value);
    }

    public function testTruncateDate()
    {
        $value = Carbon::create(2025, 1, 2, 3, 4, 5);

        $this->assertEquals(
            Carbon::create(2025, 1, 2, 0, 0, 0),
            CarbonGranularity::Date->truncate($value),
        );
    }

    public function testTruncateHour()
    {
        $value = Carbon::create(2025, 1, 2, 3, 4, 5);

        $this->assertEquals(
            Carbon::create(2025, 1, 2, 3, 0, 0),
            CarbonGranularity::Hour->truncate($value),
        );
    }

    public function testTruncateMinute()
    {
        $value = Carbon::create(2025, 1, 2, 3, 4, 5);

        $this->assertEquals(
            Carbon::create(2025, 1, 2, 3, 4, 0),
            CarbonGranularity::Minute->truncate($value),
        );
    }

    public function testTruncateSecond()
    {
        $value = Carbon::create(2025, 1, 2, 3, 4, 5)->addMicroseconds(123456);

        $this->assertEquals(
            Carbon::create(2025, 1, 2, 3, 4, 5),
            CarbonGranularity::Second->truncate($value),
        );
    }

    public function testTruncateSupportsCarbonImmutable()
    {
        $value = CarbonImmutable::create(2025, 1, 2, 3, 4, 5);

        $this->assertEquals(
            CarbonImmutable::create(2025, 1, 2, 0, 0, 0),
            CarbonGranularity::Date->truncate($value),
        );
    }
}
