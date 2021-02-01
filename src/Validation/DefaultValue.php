<?php


namespace Seier\Resting\Validation;


use Closure;
use Seier\Resting\Validation\Predicates\HasPredicates;

class DefaultValue
{

    use HasPredicates;

    private mixed $defaultValue;

    public function __construct(mixed $defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }

    public function getValue(): mixed
    {
        if ($this->defaultValue instanceof Closure) {
            return ($this->defaultValue)();
        }

        return $this->defaultValue;
    }
}