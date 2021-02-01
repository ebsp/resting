<?php


namespace Seier\Resting\Parsing;


use Seier\Resting\Support\HasPath;
use Seier\Resting\Validation\FormatsValues;
use Seier\Resting\Validation\Errors\ValidationError;

class BoolParseError implements ValidationError
{

    use HasPath;
    use FormatsValues;

    private array $expected;
    private string $actual;

    public function __construct(array $expected, string $actual)
    {
        $this->expected = $expected;
        $this->actual = $actual;
    }

    public function getMessage(): string
    {
        $expectedFormat = join(',', $this->expected);
        $actualFormat = $this->format($this->actual);

        return "Expected one of $expectedFormat, received $actualFormat instead.";
    }
}