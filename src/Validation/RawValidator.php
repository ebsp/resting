<?php

namespace Seier\Resting\Validation;

class RawValidator extends BasePrimaryValidator implements PrimaryValidator
{
    public function description(): string
    {
        return "The value can be any value.";
    }

    public function validate(mixed $value): array
    {
        return [];
    }

    public function type(): array
    {
        return [
            'type' => 'any'
        ];
    }
}