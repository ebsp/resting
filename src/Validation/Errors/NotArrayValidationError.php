<?php


namespace Seier\Resting\Validation\Errors;

use Seier\Resting\Support\HasPath;
use Seier\Resting\Validation\FormatsValues;

class NotArrayValidationError implements ValidationError
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

        return "The value was expected to be an array, $formatted received instead.";
    }
}