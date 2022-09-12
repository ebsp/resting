<?php

namespace Seier\Resting\Validation\Secondary\Comparable;

use Closure;
use Seier\Resting\Validation\Secondary\Panics;
use Seier\Resting\Validation\Secondary\SecondaryValidator;

class MaxValidator implements SecondaryValidator
{
    use Panics;

    private mixed $max;
    private bool $inclusive;
    private Closure $normalizer;
    private Closure $formatter;

    public function __construct(mixed $max, bool $inclusive, Closure $normalizer, Closure $formatter)
    {
        $this->max = $max;
        $this->inclusive = $inclusive;
        $this->normalizer = $normalizer;
        $this->formatter = $formatter;
    }

    public function description(): string
    {
        $formatted = ($this->formatter)($this->max);

        return $this->inclusive
            ? "Expects the provided number to be less than or equal to {$formatted}"
            : "Expects the provided number to be less than {$formatted}";
    }

    public function validate(mixed $value): array
    {
        $normalizedMax = ($this->normalizer)($this->max);
        $normalizedActual = ($this->normalizer)($value);

        $passes = $this->inclusive
            ? $normalizedActual <= $normalizedMax
            : $normalizedActual < $normalizedMax;

        return $passes
            ? []
            : [new MaxValidationError($this->max, $value, $this->inclusive, $this->formatter)];
    }

    public function isUnique(): bool
    {
        return true;
    }
}