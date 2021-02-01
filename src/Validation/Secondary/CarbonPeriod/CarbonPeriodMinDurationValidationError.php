<?php


namespace Seier\Resting\Validation\Secondary\CarbonPeriod;


use Carbon\Carbon;
use Carbon\CarbonInterval;
use Seier\Resting\Support\HasPath;
use Seier\Resting\Validation\Errors\ValidationError;

class CarbonPeriodMinDurationValidationError implements ValidationError
{

    use HasPath;

    private CarbonInterval $min;
    private Carbon $actualStart;
    private Carbon $actualEnd;

    public function __construct(CarbonInterval $min, Carbon $actualStart, Carbon $actualEnd)
    {
        $this->min = $min;
        $this->actualStart = $actualStart;
        $this->actualEnd = $actualEnd;
    }

    public function getMessage(): string
    {
        $actualSeconds = $this->actualEnd->diffInSeconds($this->actualStart);

        $formattedMin = $this->min->cascade()->forHumans();
        $formattedActual = CarbonInterval::seconds($actualSeconds)->cascade()->forHumans();

        return "Expected period to be greater than or equal to $formattedMin, received period of $formattedActual instead.";
    }
}