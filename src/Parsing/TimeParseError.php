<?php

namespace Seier\Resting\Parsing;

use Seier\Resting\Support\HasPath;
use Seier\Resting\Validation\Errors\ValidationError;

class TimeParseError implements ValidationError
{

    use HasPath;

    private string $actual;

    public function __construct(string $actual)
    {
        $this->actual = $actual;
    }

    public function getMessage(): string
    {
        return "Invalid time format";
    }
}