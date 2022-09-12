<?php

namespace Seier\Resting\Validation\Secondary\String;

use Seier\Resting\Validation\Secondary\Panics;
use Seier\Resting\Validation\Secondary\SecondaryValidator;

class StringRegexValidator implements SecondaryValidator
{
    use Panics;

    private string $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = $pattern;
    }

    public function description(): string
    {
        return "Expects the provided string to match pattern $this->pattern.";
    }

    public function validate(mixed $value): array
    {
        if (!is_string($value)) {
            $this->panic();
        }

        return preg_match($this->pattern, $value)
            ? []
            : [new StringRegexValidationError($this->pattern)];
    }

    public function isUnique(): bool
    {
        return false;
    }
}