<?php

namespace Seier\Resting\Validation;

use Carbon\Carbon;
use Seier\Resting\Validation\Secondary\CarbonValidation;
use Seier\Resting\Validation\Errors\NotCarbonValidationError;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

class CarbonValidator extends BasePrimaryValidator implements PrimaryValidator
{
    use CarbonValidation;

    public function description(): string
    {
        return "Value must be a valid carbon timestamp.";
    }

    public function validate(mixed $value): array
    {
        return $value instanceof Carbon
            ? $this->runValidators($value)
            : [new NotCarbonValidationError($value)];
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        return $this;
    }
}