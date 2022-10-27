<?php

namespace Seier\Resting\Validation\Predicates;

trait HasPredicates
{
    private array $predicates = [];

    public function predicatedOn(Predicate $predicate): static
    {
        array_push($this->predicates, $predicate);

        return $this;
    }

    public function getPredicates(): array
    {
        return $this->predicates;
    }

    public function hasPredicates(): bool
    {
        return !empty($this->predicates);
    }

    public function passes(ResourceContext $context): bool
    {
        foreach ($this->predicates as $predicate) {
            if (!$predicate->passes($context)) {
                return false;
            }
        }

        return true;
    }
}