<?php

namespace Seier\Resting\Validation\Secondary\Comparable;

use Closure;
use Seier\Resting\Support\HasPath;
use Seier\Resting\Validation\Errors\ValidationError;

class MinValidationError implements ValidationError
{
    use HasPath;

    private mixed $min;
    private mixed $actual;
    private bool $inclusive;
    private Closure $formatter;

    public function __construct(mixed $min, mixed $actual, bool $inclusive, Closure $formatter)
    {
        $this->min = $min;
        $this->actual = $actual;
        $this->formatter = $formatter;
        $this->inclusive = $inclusive;
    }

    public function getMessage(): string
    {
        $formattedMin = ($this->formatter)($this->min);
        $formattedActual = ($this->formatter)($this->actual);

        return $this->inclusive
            ? "Expected value to be greater than or equal to $formattedMin, received $formattedActual instead."
            : "Expected value to be greater than $formattedMin, received $formattedActual instead.";
    }
}