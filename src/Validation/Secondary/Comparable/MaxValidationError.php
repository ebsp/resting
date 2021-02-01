<?php


namespace Seier\Resting\Validation\Secondary\Comparable;


use Closure;
use Seier\Resting\Support\HasPath;
use Seier\Resting\Validation\Errors\ValidationError;

class MaxValidationError implements ValidationError
{

    use HasPath;

    private mixed $max;
    private mixed $actual;
    private bool $inclusive;
    private Closure $formatter;

    public function __construct(mixed $max, mixed $actual, bool $inclusive, Closure $formatter)
    {
        $this->max = $max;
        $this->actual = $actual;
        $this->formatter = $formatter;
        $this->inclusive = $inclusive;
    }

    public function getMessage(): string
    {
        $formattedMax = ($this->formatter)($this->max);
        $formattedActual = ($this->formatter)($this->actual);

        return $this->inclusive
            ? "Expected value to be less than or equal to $formattedMax, received $formattedActual instead."
            : "Expected value to be less than $formattedMax, received $formattedActual instead.";
    }
}