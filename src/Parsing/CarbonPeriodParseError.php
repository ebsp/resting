<?php


namespace Seier\Resting\Parsing;


use Seier\Resting\Support\HasPath;
use Seier\Resting\Validation\Errors\ValidationError;

class CarbonPeriodParseError implements ValidationError
{

    use HasPath;

    private string $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public static function tooFewArguments(): static
    {
        return new static(
            "Expected more arguments"
        );
    }

    public static function tooManyArguments(): static
    {
        return new static(
            "Expected fewer arguments."
        );
    }
}