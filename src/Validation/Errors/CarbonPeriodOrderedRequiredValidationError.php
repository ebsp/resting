<?php


namespace Seier\Resting\Validation\Errors;


use Carbon\CarbonInterface;
use Seier\Resting\Support\HasPath;

class CarbonPeriodOrderedRequiredValidationError implements ValidationError
{

    use HasPath;

    private CarbonInterface $start;
    private CarbonInterface $end;

    public function __construct(CarbonInterface $start, CarbonInterface $end)
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