<?php


namespace Seier\Resting\Validation;


use Seier\Resting\Validation\Errors\NotBoolValidationError;

class BoolValidator extends BasePrimaryValidator implements PrimaryValidator
{

    public function description(): string
    {
        return "The value must be a boolean value.";
    }

    public function validate(mixed $value): array
    {
        if (!is_bool($value)) {
            return [new NotBoolValidationError($value)];
        }

        return $this->runValidators($value);
    }
}