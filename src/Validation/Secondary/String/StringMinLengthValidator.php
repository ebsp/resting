<?php

namespace Seier\Resting\Validation\Secondary\String;

use Seier\Resting\Validation\Secondary\Panics;
use Seier\Resting\Validation\Secondary\SecondaryValidator;

class StringMinLengthValidator implements SecondaryValidator
{
    use Panics;

    private int $minLength;

    public function __construct(int $minLength)
    {
        $this->minLength = $minLength;
    }

    public function description(): string
    {
        return "Expects the length of the string to be less than or equal to $this->minLength.";
    }

    public function validate(mixed $value): array
    {
        if (!is_string($value)) {
            $this->panic();
        }

        $actualLength = strlen($value);
        return $actualLength >= $this->minLength
            ? []
            : [new StringMinLengthValidationError($this->minLength, $actualLength)];
    }

    public function isUnique(): bool
    {
        return true;
    }
}