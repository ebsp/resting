<?php

namespace Seier\Resting\Validation\Errors;

use Carbon\Carbon;
use Seier\Resting\Support\HasPath;

class CarbonPeriodOrderedRequiredValidationError implements ValidationError
{
    use HasPath;

    private Carbon $start;
    private Carbon $end;

    public function __construct(Carbon $start, Carbon $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    public function getMessage(): string
    {
        $startFormatted = $this->start->toDateTimeString();
        $endFormatted = $this->end->toDateTimeString();

        return "Expected period start to be less than or equal to end, received start $startFormatted and end $endFormatted";
    }
}