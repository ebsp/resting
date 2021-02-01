<?php


namespace Seier\Resting\Validation;


use Seier\Resting\Validation\Predicates\HasPredicates;

class RequiredValidator
{

    use HasPredicates;
    use HasDefaultValues;

    private bool $isRequired = true;
    private array $predicates = [];

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function setRequired(bool $state)
    {
        $this->isRequired = $state;
    }
}