<?php

namespace Seier\Resting\Validation\Secondary\CarbonPeriod;

use Carbon\CarbonPeriod;
use Carbon\CarbonInterval;
use Seier\Resting\Validation\Secondary\Panics;
use Seier\Resting\Validation\Secondary\SecondaryValidator;

class CarbonPeriodMaxDurationValidator implements SecondaryValidator
{
    use Panics;

    private CarbonInterval $max;
    private bool $allowWithoutEnd;

    public function __construct(CarbonInterval $max, bool $allowWithoutEnd = false)
    {
        $this->max = $max;

        $this->allowWithoutEnd = $allowWithoutEnd;
    }

    public function description(): string
    {
        $formattedInterval = $this->max->cascade()->forHumans();

        return "Expects the time period to be less than or equal to $formattedInterval.";
    }

    public function validate(mixed $value): array
    {
        if (!$value instanceof CarbonPeriod) {
            $this->panic();
        }

        if ($value->end === null && $this->allowWithoutEnd) {
            return [];
        }

        if ($value->end === null) {
            return [new CarbonPeriodMaxDurationValidationError($this->max, $value->start, $value->end)];
        }

        $start = $value->start->min($value->end);
        $end = $value->start->max($value->end);

        return $end->diffInSeconds($start) <= $this->max->totalSeconds
            ? []
            : [new CarbonPeriodMaxDurationValidationError($this->max, $value->start, $value->end)];
    }

    public function isUnique(): bool
    {
        return true;
    }
}