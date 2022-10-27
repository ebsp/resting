<?php

namespace Seier\Resting\Validation\Secondary\String;

use Seier\Resting\Validation\Secondary\Panics;
use Seier\Resting\Validation\Secondary\SecondaryValidator;

class StringLengthValidator implements SecondaryValidator
{
    use Panics;

    private int $expectedLength;

    public function __construct(int $expectedLength)
    {
        $this->expectedLength = $expectedLength;
    }

    public function description(): string
    {
        return "Expects the string to have length $this->expectedLength.";
    }

    public function validate(mixed $value): array
    {
        if (!is_string($value)) {
            $this->panic();
        }

        $actualLength = strlen($value);
        return $actualLength === $this->expectedLength
            ? []
            : [new StringLengthValidationError($this->expectedLength, $actualLength)];
    }

    public function isUnique(): bool
    {
        return true;
    }
}