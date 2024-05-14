<?php


namespace Seier\Resting\Validation\Secondary\In;


use Seier\Resting\Support\HasPath;
use Seier\Resting\Support\FormatsValues;
use Seier\Resting\Validation\Errors\ValidationError;

class InValidationError implements ValidationError
{

    use HasPath;
    use FormatsValues;

    private array $options;
    private mixed $value;

    public function __construct(array $options, mixed $value)
    {
        $this->options = $options;
        $this->value = $value;
    }

    public function getMessage(): string
    {
        $formattedOptions = $this->formatArray($this->options);
        $formattedValue = $this->format($this->value);

        return "Expected value to be one of the following options $formattedOptions, received $formattedValue instead.";
    }
}