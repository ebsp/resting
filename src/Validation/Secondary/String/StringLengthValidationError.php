<?php

namespace Seier\Resting\Validation\Secondary\String;

use Seier\Resting\Support\HasPath;
use Seier\Resting\Validation\Errors\ValidationError;

class StringLengthValidationError implements ValidationError
{
    use HasPath;

    private int $expectedLength;
    private int $actualLength;

    public function __construct(int $expectedLength, int $actualLength)
    {
        $this->expectedLength = $expectedLength;
        $this->actualLength = $actualLength;
    }

    public function getMessage(): string
    {
        return "Expected string of length $this->expectedLength, received string of length $this->actualLength.";
    }
}