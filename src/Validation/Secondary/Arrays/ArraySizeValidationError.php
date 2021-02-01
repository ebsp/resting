<?php


namespace Seier\Resting\Validation\Secondary\Arrays;


use Seier\Resting\Support\HasPath;
use Seier\Resting\Validation\Errors\ValidationError;

class ArraySizeValidationError implements ValidationError
{

    use HasPath;

    private int $expectedSize;
    private int $actualSize;

    public function __construct(int $expectedSize, int $actualSize)
    {
        $this->expectedSize = $expectedSize;
        $this->actualSize = $actualSize;
    }

    public function getMessage(): string
    {
        return "Expected array to have size $this->expectedSize, received array of size $this->actualSize.";
    }
}