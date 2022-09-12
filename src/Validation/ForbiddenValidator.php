<?php

namespace Seier\Resting\Validation;

use Seier\Resting\Validation\Predicates\HasPredicates;
use Seier\Resting\Validation\Predicates\ResourceContext;

class ForbiddenValidator
{
    use HasPredicates;

    private bool $isForbidden = false;

    public function setForbidden(bool $state)
    {
        $this->isForbidden = $state;
    }

    public function isForbidden(ResourceContext $context): bool
    {
        if ($this->hasPredicates()) {
            return $this->passes($context)
                ? $this->isForbidden
                : !$this->isForbidden;
        }

        return $this->isForbidden;
    }
}