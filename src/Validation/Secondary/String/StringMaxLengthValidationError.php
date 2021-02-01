<?php


namespace Seier\Resting\Validation\Secondary\String;


use Seier\Resting\Support\HasPath;
use Seier\Resting\Validation\Errors\ValidationError;

class StringMaxLengthValidationError implements ValidationError
{

    use HasPath;

    private int $maxLength;
    private int $actualLength;

    public function __construct(int $maxLength, int $actualLength)
    {
        $this->maxLength = $maxLength;
        $this->actualLength = $actualLength;
    }

    public function getMessage(): string
    {
        return "Expected string of length less than or equal to $this->maxLength, received string of length $this->actualLength.";
    }
}