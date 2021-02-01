<?php


namespace Seier\Resting\Validation;


use Seier\Resting\Fields\Time;
use Seier\Resting\Validation\Secondary\TimeValidation;
use Seier\Resting\Validation\Errors\NotTimeValidationError;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

class TimeValidator extends BasePrimaryValidator implements PrimaryValidator
{

    use TimeValidation;

    public function description(): string
    {
        return "The value must be a valid time value.";
    }

    public function validate(mixed $value): array
    {
        if (!$value instanceof Time) {
            return [new NotTimeValidationError($value)];
        }

        return $this->runValidators($value);
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        return $this;
    }
}