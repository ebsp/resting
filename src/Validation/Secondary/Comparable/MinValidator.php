<?php


namespace Seier\Resting\Validation\Secondary\Comparable;


use Closure;
use Seier\Resting\Validation\Secondary\Panics;
use Seier\Resting\Validation\Secondary\SecondaryValidator;

class MinValidator implements SecondaryValidator
{

    use Panics;

    private mixed $min;
    private bool $inclusive;
    private Closure $normalizer;
    private Closure $formatter;

    public function __construct(mixed $min, bool $inclusive, Closure $normalizer, Closure $formatter)
    {
        $this->min = $min;
        $this->inclusive = $inclusive;
        $this->normalizer = $normalizer;
        $this->formatter = $formatter;
    }

    public function description(): string
    {
        $formatted = ($this->formatter)($this->min);

        return $this->inclusive
            ? "Expects the provided number to be greater than or equal to {$formatted}"
            : "Expects the provided number to be greater than {$formatted}";
    }

    public function validate(mixed $value): array
    {
        $normalizedMin = ($this->normalizer)($this->min);
        $normalizedActual = ($this->normalizer)($value);

        $passes = $this->inclusive
            ? $normalizedActual >= $normalizedMin
            : $normalizedActual > $normalizedMin;

        return $passes
            ? []
            : [new MinValidationError($this->min, $value, $this->inclusive, $this->formatter)];
    }

    public function isUnique(): bool
    {
        return true;
    }
}