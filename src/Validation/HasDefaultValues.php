<?php

namespace Seier\Resting\Validation;

trait HasDefaultValues
{
    private array $defaultValues = [];

    public function withDefault(DefaultValue $defaultValue): static
    {
        $this->defaultValues[] = $defaultValue;

        return $this;
    }

    public function hasDefaultValues(): bool
    {
        return count($this->defaultValues) > 0;
    }

    public function getDefaultValues(): array
    {
        return $this->defaultValues;
    }
}