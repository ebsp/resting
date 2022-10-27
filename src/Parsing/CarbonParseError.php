<?php

namespace Seier\Resting\Parsing;

use Seier\Resting\Support\HasPath;
use Seier\Resting\Support\FormatsValues;
use Seier\Resting\Validation\Errors\ValidationError;

class CarbonParseError implements ValidationError
{
    use HasPath;
    use FormatsValues;

    private ?string $expectedFormat;
    private string $actual;

    public function __construct(?string $expectedFormat, string $actual)
    {
        $this->expectedFormat = $expectedFormat;
        $this->actual = $actual;
    }

    public function getMessage(): string
    {
        $formatted = $this->format($this->actual);

        return $this->expectedFormat
            ? "Expected value in the format $this->expectedFormat, received $formatted instead."
            : "Expected value that could be parsed to carbon timestamp, received $formatted.";
    }
}