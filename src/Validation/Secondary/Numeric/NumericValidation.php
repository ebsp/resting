<?php


namespace Seier\Resting\Validation\Secondary\Numeric;


use Seier\Resting\Fields\Field;
use Seier\Resting\Validation\Secondary\Comparable\MinValidator;
use Seier\Resting\Validation\Secondary\Comparable\MaxValidator;
use Seier\Resting\Validation\Resolver\ClosureValidatorResolver;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

trait NumericValidation
{

    protected abstract function getSupportsSecondaryValidation(): SupportsSecondaryValidation;

    public function positive(): static
    {
        $this->getSupportsSecondaryValidation()->withValidator(new PositiveNumberValidator());

        return $this;
    }

    public function min(int|float|Field $bound): static
    {
        if ($bound instanceof Field) {
            $this->getSupportsSecondaryValidation()->withLateBoundValidator(
                ClosureValidatorResolver::whenNotNullThen($bound, function (int|float $resolved) {
                    return $this->createMinValidator($resolved, inclusive: true);
                })
            );

            return $this;
        }

        $this->getSupportsSecondaryValidation()->withValidator(
            $this->createMinValidator($bound, inclusive: true)
        );

        return $this;
    }

    public function max(int|float|Field $bound): static
    {
        if ($bound instanceof Field) {
            $this->getSupportsSecondaryValidation()->withLateBoundValidator(
                ClosureValidatorResolver::whenNotNullThen($bound, function (int|float $resolved) {
                    return $this->createMaxValidator($resolved, inclusive: true);
                })
            );

            return $this;
        }

        $this->getSupportsSecondaryValidation()->withValidator(
            $this->createMaxValidator($bound, inclusive: true)
        );

        return $this;
    }

    public function lessThan(int|float|Field $bound): static
    {
        if ($bound instanceof Field) {
            $this->getSupportsSecondaryValidation()->withLateBoundValidator(
                ClosureValidatorResolver::whenNotNullThen($bound, function (int|float $resolved) {
                    return $this->createMaxValidator($resolved, inclusive: false);
                })
            );

            return $this;
        }

        $this->getSupportsSecondaryValidation()->withValidator(
            $this->createMaxValidator($bound, inclusive: false)
        );

        return $this;
    }

    public function greaterThan(int|float|Field $bound): static
    {
        if ($bound instanceof Field) {
            $this->getSupportsSecondaryValidation()->withLateBoundValidator(
                ClosureValidatorResolver::whenNotNullThen($bound, function (int|float $resolved) {
                    return $this->createMaxValidator($resolved, inclusive: false);
                })
            );

            return $this;
        }

        $this->getSupportsSecondaryValidation()->withValidator(
            $this->createMinValidator($bound, inclusive: false)
        );

        return $this;
    }

    public function between(int|float $min, int|float $max): static
    {
        $this->min($min);
        $this->max($max);

        return $this;
    }

    private function createMinValidator(int|float $min, bool $inclusive): MinValidator
    {
        return new MinValidator(
            $min,
            inclusive: $inclusive,
            normalizer: fn(int|float $value) => $value,
            formatter: fn(int|float $value) => $value,
        );
    }

    private function createMaxValidator(int|float $max, bool $inclusive): MaxValidator
    {
        return new MaxValidator(
            $max,
            inclusive: $inclusive,
            normalizer: fn(int|float $value) => $value,
            formatter: fn(int|float $value) => $value,
        );
    }
}