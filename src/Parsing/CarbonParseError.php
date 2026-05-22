<?php


namespace Seier\Resting\Parsing;


use Seier\Resting\Support\HasPath;
use Seier\Resting\Support\FormatsValues;
use Seier\Resting\Validation\Errors\ValidationError;

class CarbonParseError implements ValidationError
{

    use HasPath;
    use FormatsValues;

    private string $actual;

    public function __construct(string $actual)
    {
        $this->actual = $actual;
    }

    public function getMessage(): string
    {
        $formatted = $this->format($this->actual);

        return "Expected value that could be parsed to carbon timestamp, received '$formatted'.";
    }
}
