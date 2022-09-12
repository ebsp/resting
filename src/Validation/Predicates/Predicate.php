<?php

namespace Seier\Resting\Validation\Predicates;

interface Predicate
{
    public function description(ResourceContext $context): string;

    public function passes(ResourceContext $context): bool;
}