<?php


namespace Seier\Resting\Validation\Secondary\String;


use Seier\Resting\Support\HasPath;
use Seier\Resting\Validation\Errors\ValidationError;

class StringRegexValidationError implements ValidationError
{

    use HasPath;

    private string $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = $pattern;
    }

    public function getMessage(): string
    {
        return "Expected string to have pattern $this->pattern, but value did not match pattern.";
    }
}