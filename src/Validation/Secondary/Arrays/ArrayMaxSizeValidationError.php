<?php

namespace Seier\Resting\Validation\Secondary\Arrays;

use Seier\Resting\Support\HasPath;
use Seier\Resting\Validation\Errors\ValidationError;

class ArrayMaxSizeValidationError implements ValidationError
{
    use HasPath;

    private int $maxSize;
    private int $actualSize;

    public function __construct(int $maxSize, int $actualSize)
    {
        $this->maxSize = $maxSize;
        $this->actualSize = $actualSize;
    }

    public function getMessage(): string
    {
        return "Expected array to have size less than or equal to $this->maxSize, received array of size $this->actualSize instead.";
    }
}