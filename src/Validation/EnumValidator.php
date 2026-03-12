<?php

namespace Seier\Resting\Validation;

use ReflectionEnum;
use Seier\Resting\Support\FormatsValues;
use Seier\Resting\Validation\Secondary\In\InValidation;
use Seier\Resting\Validation\Errors\EnumValidationError;
use Seier\Resting\Exceptions\RestingDefinitionException;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

class EnumValidator extends BasePrimaryValidator implements PrimaryValidator
{
    use FormatsValues;
    use InValidation;

    private ReflectionEnum $reflectionEnum;

    public function __construct(ReflectionEnum $reflectionEnum)
    {
        $this->reflectionEnum = $reflectionEnum;

        if (!$reflectionEnum->isBacked()) {
            throw new RestingDefinitionException("EnumValidator only supports enums backed by strings.");
        }

        foreach ($reflectionEnum->getCases() as $case) {
            if (!is_string($case->getBackingValue())) {
                throw new RestingDefinitionException("EnumValidator only supports enums backed by strings.");
            }
        }
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
        foreach ($this->reflectionEnum->getCases() as $case) {
            if ($value === $case->getValue()) {
                return $this->runValidators($value);
            }
        }

        return [new EnumValidationError($this->reflectionEnum, $value)];
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        return $this;
    }

    public function type(): array
    {
        $options = [];
        foreach ($this->reflectionEnum->getCases() as $case) {
            $options[] = $case->getBackingValue();
        }

        return [
            'type' => 'string',
            'enum' => $options,
        ];
    }
}
