<?php


namespace Seier\Resting\Validation;


use Seier\Resting\Validation\Errors\NotArrayValidationError;
use Seier\Resting\Validation\Errors\NullableValidationError;
use Seier\Resting\Validation\Secondary\Arrays\ArrayValidation;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

class ArrayValidator extends BasePrimaryValidator implements PrimaryValidator
{

    use ArrayValidation;

    private ?PrimaryValidator $elementValidator = null;
    private bool $allowNulls = false;

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
        foreach ($value as $elementIndex => $elementValue) {

            if (!$this->allowNulls && $elementValue === null) {
                $errors[] = (new NullableValidationError)->prependPath($elementIndex);
            } else if ($this->allowNulls && $elementValue === null) {
                continue;
            } else if ($this->elementValidator) {
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

    public function allowNulls(bool $state = true): static
    {
        $this->allowNulls = $state;

        return $this;
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        return $this;
    }
}