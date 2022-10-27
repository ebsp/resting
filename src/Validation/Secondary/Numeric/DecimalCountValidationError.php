<?php

namespace Seier\Resting\Validation\Secondary\Numeric;

use Exception;
use Seier\Resting\Support\HasPath;
use Seier\Resting\Validation\Errors\ValidationError;

class DecimalCountValidationError implements ValidationError
{
    use HasPath;

    private int|float $actual;
    private int $numberOfDecimals;
    private ?int $minDecimals;
    private ?int $maxDecimals;

    public function __construct(float|int $actual, $numberOfDecimals, ?int $minDecimals, ?int $maxDecimals)
    {
        $this->actual = $actual;
        $this->numberOfDecimals = $numberOfDecimals;
        $this->minDecimals = $minDecimals;
        $this->maxDecimals = $maxDecimals;
    }

    public function getMessage(): string
    {
        $expected = $this->makeExpectedMessage();

        return "$expected, received $this->actual ($this->numberOfDecimals decimals) instead.";
    }

    private function makeExpectedMessage(): string
    {
        if ($this->minDecimals !== null && $this->maxDecimals !== null) {
            return $this->minDecimals === $this->maxDecimals
                ? "Expected the provided number to have $this->minDecimals decimals"
                : "Expected the provided number to have between $this->minDecimals and $this->maxDecimals decimals";
        }

        if ($this->minDecimals !== null) {
            return "Expected the provided number to have $this->minDecimals or more decimals";
        }

        if ($this->maxDecimals !== null) {
            return "Expected the provided number to have $this->maxDecimals or fewer decimals";
        }

        throw new Exception('unsupported');
    }
}