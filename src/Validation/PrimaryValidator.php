<?php


namespace Seier\Resting\Validation;


use Seier\Resting\Validation\Resolver\ValidatorResolver;
use Seier\Resting\Validation\Secondary\SecondaryValidator;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

interface PrimaryValidator extends Validator, SupportsSecondaryValidation
{

    public function getSecondaryValidators(): array;

    public function getValidatorResolvers(): array;

    public function withValidator(SecondaryValidator $validator): static;

    public function withLateBoundValidator(ValidatorResolver $resolver): static;
    
    public function type(): array;
}