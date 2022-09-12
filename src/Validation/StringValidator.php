<?php

namespace Seier\Resting\Validation;

use Seier\Resting\Validation\Secondary\Enum\EnumValidation;
use Seier\Resting\Validation\Errors\NotStringValidationError;
use Seier\Resting\Validation\Secondary\String\StringValidation;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

class StringValidator extends BasePrimaryValidator implements PrimaryValidator
{
    use StringValidation;
    use EnumValidation;

    public function description(): string
    {
        return "The value must be a string";
    }

    public function validate(mixed $value): array
    {
        if (!is_string($value)) {
            return [new NotStringValidationError($value)];
        }

        return $this->runValidators($value);
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        return $this;
    }
}