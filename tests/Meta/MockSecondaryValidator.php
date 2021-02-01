<?php


namespace Seier\Resting\Tests\Meta;


use Closure;
use Seier\Resting\Validation\Secondary\SecondaryValidator;

class MockSecondaryValidator implements SecondaryValidator
{

    private Closure $validator;
    private bool $unique = false;

    public function __construct(Closure $validator)
    {
        $this->validator = $validator;
    }

    public function unique(bool $unique = true): static
    {
        $this->unique = $unique;

        return $this;
    }

    public static function pass(): static
    {
        return new static(fn() => []);
    }

    public static function fail(): static
    {
        return new static(fn() => [new MockSecondaryValidationError()]);
    }

    public function description(): string
    {
        return "mock";
    }

    public function validate(mixed $value): array
    {
        return ($this->validator)($value);
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }
}