<?php

namespace Seier\Resting\Validation\Errors;

use Seier\Resting\Support\HasPath;
use Seier\Resting\Support\FormatsValues;

class UnknownUnionDiscriminatorValidationError implements ValidationError
{
    use HasPath;
    use FormatsValues;

    private array $expected;
    private mixed $actual;

    public function __construct(array $expected, mixed $actual)
    {
        $this->expected = $expected;
        $this->actual = $actual;
    }

    public function getMessage(): string
    {
        $formattedExpected = $this->format($this->expected, showType: false);
        $formattedActual = $this->format($this->actual);

        return "Unknown discriminator value, expected one of $formattedExpected, received $formattedActual instead.";
    }
}