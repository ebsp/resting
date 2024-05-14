<?php


namespace Seier\Resting\Validation\Secondary\Enum;


use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

trait InValidation
{

    protected abstract function getSupportsSecondaryValidation(): SupportsSecondaryValidation;

    public function in(array $options): static
    {
        $this->getSupportsSecondaryValidation()->withValidator(
            new InValidator($options)
        );

        return $this;
    }
}