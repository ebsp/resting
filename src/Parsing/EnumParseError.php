<?php


namespace Seier\Resting\Parsing;


use ReflectionEnum;
use Seier\Resting\Support\HasPath;
use Seier\Resting\Support\FormatsValues;
use Seier\Resting\Validation\Errors\ValidationError;

class EnumParseError implements ValidationError
{

    use HasPath;
    use FormatsValues;

    private string $value;
    private ReflectionEnum $reflectionEnum;

    public function __construct(string $value, ReflectionEnum $reflectionEnum)
    {
        $this->value = $value;
        $this->reflectionEnum = $reflectionEnum;
    }

    public function getMessage(): string
    {
        $rawValues = [];
        foreach ($this->reflectionEnum->getCases() as $case) {
            $rawValues[] = $case->getBackingValue();
        }

        $formattedOptions = $this->format($rawValues, showType: false);
        $formattedValue = $this->format($this->value);

        return "Expected one of ($formattedOptions), received $formattedValue instead.";
    }
}