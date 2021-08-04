<?php


namespace Seier\Resting\Validation\Errors;


use Seier\Resting\Support\HasPath;
use Seier\Resting\Support\FormatsValues;

class NotCarbonPeriodValidationError
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

        return "The value is required to be a carbon period, $formatted received instead.";
    }
}