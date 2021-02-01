<?php


namespace Seier\Resting\Validation\Secondary\Arrays;


use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

trait ArrayValidation
{

    protected abstract function getSupportsSecondaryValidation(): SupportsSecondaryValidation;

    public function size(int $expected): static
    {
        $this->getSupportsSecondaryValidation()->withValidator(
            new ArraySizeValidator($expected)
        );

        return $this;
    }

    public function notEmpty(): static
    {
        $this->getSupportsSecondaryValidation()->withValidator(
            new ArrayMinSizeValidator(1)
        );

        return $this;
    }

    public function empty(): static
    {
        $this->getSupportsSecondaryValidation()->withValidator(
            new ArraySizeValidator(0)
        );

        return $this;
    }

    public function minSize(int $minSize): static
    {
        $this->getSupportsSecondaryValidation()->withValidator(
            new ArrayMinSizeValidator($minSize)
        );

        return $this;
    }

    public function maxSize(int $maxSize): static
    {
        $this->getSupportsSecondaryValidation()->withValidator(
            new ArrayMaxSizeValidator($maxSize)
        );

        return $this;
    }
}