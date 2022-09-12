<?php

namespace Seier\Resting\Validation;

use Seier\Resting\Validation\Predicates\HasPredicates;

class NullableValidator
{
    use HasPredicates;
    use HasDefaultValues;

    private bool $isNullable = false;

    public function setNullable(bool $state)
    {
        $this->isNullable = $state;
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }
}