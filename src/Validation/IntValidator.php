<?php


namespace Seier\Resting\Validation;


use Seier\Resting\Validation\Errors\NotIntValidationError;
use Seier\Resting\Validation\Secondary\Numeric\NumericValidation;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

class IntValidator extends BasePrimaryValidator implements PrimaryValidator
{

    use NumericValidation;

    public function description(): string
    {
        return "Expects an integer value.";
    }

    public function validate(mixed $value): array
    {
        if (!is_int($value)) {
            return [new NotIntValidationError($value)];
        }

        return $this->runValidators($value);
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        return $this;
    }

    public function type(): array
    {
        return [
            'type' => 'integer',
            'format' => 'int64',
        ];
    }
}