<?php

namespace Seier\Resting\Fields;

use Carbon\CarbonInterface;

enum CarbonGranularity: string
{
    case Date = 'date';
    case Hour = 'hour';
    case Minute = 'minute';
    case Second = 'second';
    case Millisecond = 'millisecond';

    public function truncate(CarbonInterface $value): CarbonInterface
    {
        return match ($this) {
            self::Date => $value->startOfDay(),
            self::Hour => $value->startOfHour(),
            self::Minute => $value->startOfMinute(),
            self::Second => $value->startOfSecond(),
            self::Millisecond => $value->startOfMillisecond(),
        };
    }
}
