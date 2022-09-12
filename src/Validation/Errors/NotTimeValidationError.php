<?php

namespace Seier\Resting\Validation\Errors;

use Seier\Resting\Support\HasPath;
use Seier\Resting\Support\FormatsValues;

class NotTimeValidationError implements ValidationError
{
    use HasPath;
    use FormatsValues;

    private mixed $actual;

    public function __construct(mixed $actual)
    {
        $this->actual = $actual;
    }

    public function getMessage(): string
    {
        $formatted = $this->format($this->actual);

        return "Expected instance of time, received $formatted instead.";
    }
}