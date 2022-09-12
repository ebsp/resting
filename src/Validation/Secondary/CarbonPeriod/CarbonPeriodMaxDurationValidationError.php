<?php

namespace Seier\Resting\Validation\Secondary\CarbonPeriod;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Seier\Resting\Support\HasPath;
use Seier\Resting\Validation\Errors\ValidationError;

class CarbonPeriodMaxDurationValidationError implements ValidationError
{
    use HasPath;

    private CarbonInterval $max;
    private Carbon $start;
    private ?Carbon $end;

    public function __construct(CarbonInterval $max, Carbon $start, ?Carbon $end)
    {
        $this->max = $max;
        $this->start = $start;
        $this->end = $end;
    }

    public function getMessage(): string
    {
        $formattedMax = $this->max->cascade()->forHumans();
        if ($this->end === null) {
            return "Expected period to be less than or equal to $formattedMax, received period without end.";
        }

        $actualSeconds = $this->end->diffInSeconds($this->start);
        $actualFormatted = CarbonInterval::seconds($actualSeconds)->cascade()->forHumans();

        return "Expected period to be less than or equal to $formattedMax, received period of $actualFormatted instead.";
    }
}