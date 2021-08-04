<?php


namespace Seier\Resting\Validation\Errors;


use Seier\Resting\Support\HasPath;
use Seier\Resting\Support\FormatsValues;

class NotStringValidationError implements ValidationError
{

    use HasPath;
    use FormatsValues;

    private mixed $value;

    public function __construct(mixed $value)
    {
        $this->value = $value;
    }

    public function getMessage(): string
    {
        $formatted = $this->format($this->value);

        return "The value is required to be a string, $formatted received instead.";
    }
}