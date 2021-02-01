<?php


namespace Seier\Resting\Validation\Secondary\Enum;


use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

trait EnumValidation
{

    protected abstract function getSupportsSecondaryValidation(): SupportsSecondaryValidation;

    public function in(array $options): static
    {
        $this->getSupportsSecondaryValidation()->withValidator(
            new EnumValidator($options)
        );

        return $this;
    }
}