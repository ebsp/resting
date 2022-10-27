<?php

namespace Seier\Resting\Validation\Secondary\Enum;

use Seier\Resting\Support\FormatsValues;
use Seier\Resting\Validation\Secondary\SecondaryValidator;

class EnumValidator implements SecondaryValidator
{
    use FormatsValues;

    private array $options;

    public function __construct(array $options)
    {
        $this->options = array_values($options);
    }

    public function description(): string
    {
        $formatted = $this->format($this->options);

        return "Expects the value to be one of the following: $formatted.";
    }

    public function validate(mixed $value): array
    {
        return in_array($value, $this->options, strict: true)
            ? []
            : [new EnumValidationError($this->options, $value)];
    }

    public function isUnique(): bool
    {
        return true;
    }
}