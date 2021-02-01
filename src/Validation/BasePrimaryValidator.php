<?php


namespace Seier\Resting\Validation;


use Seier\Resting\Validation\Resolver\ValidatorResolver;
use Seier\Resting\Validation\Secondary\SecondaryValidator;

abstract class BasePrimaryValidator implements PrimaryValidator
{

    protected array $secondaryValidators = [];
    protected array $secondaryValidatorResolvers = [];

    public function withValidator(SecondaryValidator $validator): static
    {
        if ($validator->isUnique()) {
            $this->secondaryValidators[$validator::class] = $validator;
        } else {
            $this->secondaryValidators[] = $validator;
        }

        return $this;
    }

    public function withLateBoundValidator(ValidatorResolver $resolver): static
    {
        $this->secondaryValidatorResolvers[] = $resolver;

        return $this;
    }

    protected function runValidators(mixed $value): array
    {
        $errors = [];
        foreach ($this->secondaryValidators as $secondaryValidator) {
            foreach ($secondaryValidator->validate($value) ?? [] as $error) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    public function getSecondaryValidators(): array
    {
        return $this->secondaryValidators;
    }

    public function getValidatorResolvers(): array
    {
        return $this->secondaryValidatorResolvers;
    }
}
