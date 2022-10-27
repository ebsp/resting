<?php

namespace Seier\Resting\Validation\Secondary\Arrays;

use Seier\Resting\Support\HasPath;
use Seier\Resting\Validation\Errors\ValidationError;

class ArrayMinSizeValidationError implements ValidationError
{
    use HasPath;

    private int $minSize;
    private int $actualSize;

    public function __construct(int $minSize, int $actualSize)
    {
        $this->minSize = $minSize;
        $this->actualSize = $actualSize;
    }

    public function getMessage(): string
    {
        return "Expected array to have size greater than or equal to $this->minSize, received array of size $this->actualSize instead.";
    }
}