<?php


namespace Seier\Resting\Validation\Secondary\Numeric;


use Seier\Resting\Support\HasPath;
use Seier\Resting\Validation\Errors\ValidationError;

class PositiveNumberValidationError implements ValidationError
{

    use HasPath;

    private int|float $actual;

    public function __construct(float|int $actual)
    {
        $this->actual = $actual;
    }

    public function getMessage(): string
    {
        return "Expected value to be positive, received $this->actual instead.";
    }
}