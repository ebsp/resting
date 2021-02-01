<?php


namespace Seier\Resting\Validation\Secondary\Numeric;


use Seier\Resting\Validation\Secondary\Panics;
use Seier\Resting\Validation\Secondary\SecondaryValidator;

class PositiveNumberValidator implements SecondaryValidator
{

    use Panics;

    public function description(): string
    {
        return "Expects the provided number to be positive";
    }

    public function validate(mixed $value): array
    {
        if (!is_numeric($value)) {
            $this->panic();
        }

        return $value > 0
            ? []
            : [new PositiveNumberValidationError($value)];
    }

    public function isUnique(): bool
    {
        return true;
    }
}