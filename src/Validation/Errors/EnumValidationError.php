<?php


namespace Seier\Resting\Validation\Errors;


use ReflectionEnum;
use Seier\Resting\Support\HasPath;
use Seier\Resting\Support\FormatsValues;

class EnumValidationError implements ValidationError
{
    use HasPath;
    use FormatsValues;

    private ReflectionEnum $reflectionEnum;
    private mixed $value;

    public function __construct(ReflectionEnum $reflectionEnum, mixed $value)
    {
        $this->reflectionEnum = $reflectionEnum;
        $this->value = $value;
    }

    public function getMessage(): string
    {
        return is_string($this->value)
            ? $this->getStringBasedMessage()
            : $this->getEnumBasedMessage();
    }

    private function getStringBasedMessage(): string
    {
        $stringBasedOptions = [];
        foreach ($this->reflectionEnum->getCases() as $case) {
            $stringBasedOptions[] = $case->getBackingValue();
        }

        $formattedOptions = $this->formatArray($stringBasedOptions);
        $formattedValue = $this->format($this->value);

        return "Expected value to be one of the following options $formattedOptions, received $formattedValue instead.";
    }

    private function getEnumBasedMessage(): string
    {
        $nameBasedOptions = [];
        foreach ($this->reflectionEnum->getCases() as $case) {
            $nameBasedOptions[] = $case->getName();
        }

        $formattedOptions = join('|', $nameBasedOptions);
        $formattedType = $this->reflectionEnum->getName();
        $formattedValue = $this->formatType($this->value);

        return "Expected one of $formattedType enum values ($formattedOptions), received $formattedValue instead.";
    }
}