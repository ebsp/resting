<?php

namespace Seier\Resting\Validation\Secondary\String;

use Seier\Resting\Support\HasPath;
use Seier\Resting\Validation\Errors\ValidationError;

class StringMinLengthValidationError implements ValidationError
{
    use HasPath;

    private int $minLength;
    private int $actualLength;

    public function __construct(int $minLength, int $actualLength)
    {
        $this->minLength = $minLength;
        $this->actualLength = $actualLength;
    }

    public function getMessage(): string
    {
        return "Expected string of length greater than or equal to $this->minLength, received string of length $this->actualLength.";
    }
}