<?php


namespace Seier\Resting\Parsing;


use Seier\Resting\Support\HasPath;
use Seier\Resting\Validation\FormatsValues;
use Seier\Resting\Validation\Errors\ValidationError;

class IntParseError implements ValidationError
{

    use HasPath;
    use FormatsValues;

    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getMessage(): string
    {
        $formatted = $this->format($this->value);

        return "Expected value that can be parsed as int ([0-9]+), received $formatted instead.";
    }
}