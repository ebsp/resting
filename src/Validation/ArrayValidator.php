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
    private bool $allowsNullElements = false;

    public function description(): string
    {
        $result = "The value must be an array.";

        if ($this->elementValidator !== null) {
            $result .= " ";
            $result .= "The following must hold for the elements: ";
            $result .= $this->elementValidator->description();
        }

        $result .= " ";
        if ($this->allowsNullElements) {
            $result .= "The elements can be null.";
        } else {
            $result .= "The elements must not be null.";
        }

        return $result;
    }

    public function validate(mixed $value): array
    {
        if (!is_array($value)) {
            return [new NotArrayValidationError($value)];
        }

        $errors = $this->runValidators($value);
        foreach ($value as $elementIndex => $elementValue) {

            if (!$this->allowsNullElements && $elementValue === null) {
                $errors[] = (new NullableValidationError)->prependPath($elementIndex);
            } else if ($this->allowsNullElements && $elementValue === null) {
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

    public function getElementValidator(): ?PrimaryValidator
    {
        return $this->elementValidator;
    }

    public function allowNullElements(bool $state = true): static
    {
        $this->allowsNullElements = $state;

        return $this;
    }

    public function allowsNullElements(): bool
    {
        return $this->allowsNullElements;
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        return $this;
    }

    public function type(): array
    {
        $elementsAreNullable = $this->allowsNullElements();
        $elementValidator = $this->getElementValidator();

        $items = match ($elementValidator ? $elementValidator::class : null) {
            IntValidator::class,
            StringValidator::class,
            NumberValidator::class,
            ArrayValidation::class,
            EnumValidator::class,
            BoolValidator::class,
            CarbonValidator::class,
            CarbonPeriodValidator::class,
            TimeValidator::class => $elementValidator->type(),
            default => [],
        };

        $items['nullable'] = $elementsAreNullable;

        return [
            'type' => 'array',
            'items' => $items,
        ];
    }
}