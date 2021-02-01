<?php


namespace Seier\Resting\Validation;


use Seier\Resting\Validation\Errors\NotArrayValidationError;
use Seier\Resting\Validation\Secondary\Arrays\ArrayValidation;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

class ArrayValidator extends BasePrimaryValidator implements PrimaryValidator
{

    use ArrayValidation;

    private ?PrimaryValidator $elementValidator = null;

    public function description(): string
    {
        return "The value must be an array";
    }

    public function validate(mixed $value): array
    {
        if (!is_array($value)) {
            return [new NotArrayValidationError($value)];
        }

        $errors = $this->runValidators($value);
        if ($this->elementValidator) {
            foreach ($value as $elementIndex => $elementValue) {
                $elementErrors = $this->elementValidator->validate($elementValue);
                foreach ($elementErrors as $elementError) {
                    $errors[] = $elementError->prependPath($elementIndex);
                }
            }
        }

        return $errors;
    }

    public function setElementValidator(PrimaryValidator $validator)
    {
        $this->elementValidator = $validator;
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        return $this;
    }
}