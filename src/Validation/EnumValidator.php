<?php

namespace Seier\Resting\Validation;

use ReflectionEnum;
use Seier\Resting\Support\FormatsValues;

class EnumValidator extends BasePrimaryValidator implements PrimaryValidator
{
    use FormatsValues;

    private ReflectionEnum $reflectionEnum;

    public function __construct(ReflectionEnum $reflectionEnum)
    {
        $this->reflectionEnum = $reflectionEnum;
    }

    public function description(): string
    {
        $cases = [];
        foreach ($this->reflectionEnum->getCases() as $case) {
            $cases[] = $case->getBackingValue();
        }

        $formatted = $this->formatArray($cases);

        return "The value must be one of $formatted.";
    }

    public function validate(mixed $value): array
    {
        // Done in EnumField

        return [];
    }
}
