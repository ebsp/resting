<?php


namespace Seier\Resting\Validation\Secondary\Anonymous;


use Closure;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

trait AnonymousValidation
{

    protected abstract function getSupportsSecondaryValidation(): SupportsSecondaryValidation;

    public function validateThat(string $description, Closure $validator): static
    {
        $instance = new AnonymousSecondaryValidator($description, $validator);
        $this->getSupportsSecondaryValidation()->withValidator($instance);

        return $this;
    }
}