<?php


namespace Seier\Resting\Validation;


use Seier\Resting\Validation\Errors\NotNumberValidationError;
use Seier\Resting\Validation\Secondary\Numeric\NumericValidation;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

class NumberValidator extends BasePrimaryValidator implements PrimaryValidator
{

    use NumericValidation;

    public function description(): string
    {
        return "Expects the value to be a real number.";
    }

    public function validate(mixed $value): array
    {
        if (!is_numeric($value)) {
            return [new NotNumberValidationError($value)];
        }

        return $this->runValidators($value);
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        return $this;
    }
}