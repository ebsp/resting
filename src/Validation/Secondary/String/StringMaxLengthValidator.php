<?php


namespace Seier\Resting\Validation\Secondary\String;


use Seier\Resting\Validation\Secondary\Panics;
use Seier\Resting\Validation\Secondary\SecondaryValidator;

class StringMaxLengthValidator implements SecondaryValidator
{

    use Panics;

    private int $maxLength;

    public function __construct(int $maxLength)
    {
        $this->maxLength = $maxLength;
    }

    public function description(): string
    {
        return "Expects the length of the to be less than or equal to $this->maxLength.";
    }

    public function validate(mixed $value): array
    {
        if (!is_string($value)) {
            $this->panic();
        }

        $actualLength = strlen($value);
        return $actualLength <= $this->maxLength
            ? []
            : [new StringMaxLengthValidationError($this->maxLength, $actualLength)];
    }

    public function isUnique(): bool
    {
        return true;
    }
}