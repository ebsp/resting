<?php


namespace Seier\Resting\Validation\Secondary\Numeric;


use Seier\Resting\Validation\Secondary\Panics;
use Seier\Resting\Validation\Secondary\SecondaryValidator;

class DecimalCountValidator implements SecondaryValidator
{

    use Panics;

    private ?int $minDecimals;
    private ?int $maxDecimals;

    public function __construct(?int $minDecimals = null, ?int $maxDecimals = null)
    {
        $this->minDecimals = $minDecimals;
        $this->maxDecimals = $maxDecimals;
    }

    public function description(): string
    {
        if ($this->minDecimals !== null && $this->maxDecimals !== null) {
            return $this->minDecimals === $this->maxDecimals
                ? "Expects the provided number to have $this->minDecimals decimals."
                : "Expects the provided number to have between $this->minDecimals and $this->maxDecimals decimals.";
        }

        if ($this->minDecimals !== null) {
            return "Expects the provided number to have $this->minDecimals or more decimals.";
        }

        if ($this->maxDecimals !== null) {
            return "Expects the provided number to have $this->maxDecimals or fewer decimals.";
        }

        $this->panic();
    }

    public function validate(mixed $value): array
    {
        if (!is_numeric($value)) {
            $this->panic();
        }

        $numberOfDecimals = is_int($value)
            ? 0
            : strlen($value) - strrpos($value, '.') - 1;

        if ($this->maxDecimals !== null && $numberOfDecimals > $this->maxDecimals) {
            return [new DecimalCountValidationError($value, $this->minDecimals, $this->maxDecimals)];
        }

        if ($this->minDecimals !== null && $numberOfDecimals < $this->minDecimals) {
            return [new DecimalCountValidationError($value, $this->minDecimals, $this->maxDecimals)];
        }

        return [];
    }

    public function isUnique(): bool
    {
        return true;
    }
}