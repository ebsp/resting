<?php

namespace Seier\Resting\Validation\Secondary\Arrays;

use Seier\Resting\Validation\Secondary\Panics;
use Seier\Resting\Validation\Secondary\SecondaryValidator;

class ArraySizeValidator implements SecondaryValidator
{
    use Panics;

    private int $expectedSize;

    public function __construct(int $size)
    {
        $this->expectedSize = $size;
    }

    public function description(): string
    {
        return "Expects array to have size equal to $this->expectedSize.";
    }

    public function validate(mixed $value): array
    {
        if (!is_array($value)) {
            $this->panic();
        }

        $actualSize = count($value);
        return $actualSize === $this->expectedSize
            ? []
            : [new ArraySizeValidationError($this->expectedSize, $actualSize)];
    }

    public function isUnique(): bool
    {
        return true;
    }
}