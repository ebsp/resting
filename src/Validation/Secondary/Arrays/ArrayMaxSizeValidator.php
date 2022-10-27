<?php

namespace Seier\Resting\Validation\Secondary\Arrays;

use Seier\Resting\Validation\Secondary\Panics;
use Seier\Resting\Validation\Secondary\SecondaryValidator;

class ArrayMaxSizeValidator implements SecondaryValidator
{
    use Panics;

    private int $maxSize;

    public function __construct(int $maxSize)
    {
        $this->maxSize = $maxSize;
    }

    public function description(): string
    {
        return "Expects array to have size less than or equal to $this->maxSize.";
    }

    public function validate(mixed $value): array
    {
        if (!is_array($value)) {
            $this->panic();
        }

        $actualSize = count($value);
        return $actualSize <= $this->maxSize
            ? []
            : [new ArrayMaxSizeValidationError($this->maxSize, $actualSize)];
    }

    public function isUnique(): bool
    {
        return true;
    }
}