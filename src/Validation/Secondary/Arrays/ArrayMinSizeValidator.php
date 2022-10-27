<?php

namespace Seier\Resting\Validation\Secondary\Arrays;

use Seier\Resting\Validation\Secondary\Panics;
use Seier\Resting\Validation\Secondary\SecondaryValidator;

class ArrayMinSizeValidator implements SecondaryValidator
{
    use Panics;

    private int $minSize;

    public function __construct(int $minSize)
    {
        $this->minSize = $minSize;
    }

    public function description(): string
    {
        return "Expects array to have size greater than or equal to $this->minSize.";
    }

    public function validate(mixed $value): array
    {
        if (!is_array($value)) {
            $this->panic();
        }

        $actualSize = count($value);
        return $actualSize >= $this->minSize
            ? []
            : [new ArrayMinSizeValidationError($this->minSize, $actualSize)];
    }

    public function isUnique(): bool
    {
        return true;
    }
}