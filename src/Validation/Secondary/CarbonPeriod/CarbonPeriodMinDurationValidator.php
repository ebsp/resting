<?php


namespace Seier\Resting\Validation\Secondary\CarbonPeriod;


use Carbon\CarbonPeriod;
use Carbon\CarbonInterval;
use Seier\Resting\Validation\Secondary\Panics;
use Seier\Resting\Validation\Secondary\SecondaryValidator;

class CarbonPeriodMinDurationValidator implements SecondaryValidator
{

    use Panics;

    private CarbonInterval $min;

    public function __construct(CarbonInterval $min)
    {
        $this->min = $min;
    }

    public function description(): string
    {
        $formattedInterval = $this->min->cascade()->forHumans();

        return "Expects the time period to be greater than or equal to $formattedInterval.";
    }

    public function validate(mixed $value): array
    {
        if (!$value instanceof CarbonPeriod) {
            $this->panic();
        }

        $start = $value->start->min($value->end);
        $end = $value->start->max($value->end);

        $actualSeconds = $end->diffInSeconds($start);

        return $actualSeconds >= $this->min->totalSeconds
            ? []
            : [new CarbonPeriodMinDurationValidationError($this->min, $value->start, $value->end)];
    }

    public function isUnique(): bool
    {
        return true;
    }
}