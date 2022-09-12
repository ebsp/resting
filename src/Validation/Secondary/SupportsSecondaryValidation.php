<?php

namespace Seier\Resting\Validation\Secondary;

use Seier\Resting\Validation\Resolver\ValidatorResolver;

interface SupportsSecondaryValidation
{
    public function withValidator(SecondaryValidator $validator): static;

    public function withLateBoundValidator(ValidatorResolver $resolver): static;
}