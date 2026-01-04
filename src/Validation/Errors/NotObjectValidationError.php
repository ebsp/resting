<?php


namespace Seier\Resting\Validation\Errors;

use Seier\Resting\Support\HasPath;
use Seier\Resting\Support\FormatsValues;

class NotObjectValidationError implements ValidationError
{
    use FormatsValues;
    use HasPath;

    private mixed $value;

    public function __construct(mixed $value)
    {
        $this->value = $value;
    }

    public function getMessage(): string
    {
        $formatted = $this->format($this->value);

        return "The value was expected to be an object, $formatted received instead.";
    }
}