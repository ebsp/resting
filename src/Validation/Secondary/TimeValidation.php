<?php

namespace Seier\Resting\Validation\Secondary;

use Seier\Resting\Fields\Time;
use Seier\Resting\Fields\Field;
use Seier\Resting\Fields\TimeField;
use Seier\Resting\Validation\Resolver\ClosureValidatorResolver;
use Seier\Resting\Validation\Secondary\Comparable\MinValidator;
use Seier\Resting\Validation\Secondary\Comparable\MaxValidator;

trait TimeValidation
{
    protected abstract function getSupportsSecondaryValidation(): SupportsSecondaryValidation;

    public function min(Time|Field $min): static
    {
        if ($min instanceof Field) {
            $this->getSupportsSecondaryValidation()->withLateBoundValidator(
                ClosureValidatorResolver::whenNotNullThen($min, function (Time $resolved) {
                    return $this->createMinValidator($resolved, inclusive: true);
                })
            );

            return $this;
        }

        $this->getSupportsSecondaryValidation()->withValidator(
            $this->createMinValidator($min, inclusive: true),
        );

        return $this;
    }

    public function max(Time|Field $max): static
    {
        if ($max instanceof TimeField) {
            $this->getSupportsSecondaryValidation()->withLateBoundValidator(
                ClosureValidatorResolver::whenNotNullThen($max, function (Time $resolved) {
                    return $this->createMaxValidator($resolved, inclusive: true);
                })
            );

            return $this;
        }

        $this->getSupportsSecondaryValidation()->withValidator(
            $this->createMaxValidator($max, inclusive: true),
        );

        return $this;
    }

    public function after(Time|Field $bound): static
    {
        if ($bound instanceof Field) {
            $this->getSupportsSecondaryValidation()->withLateBoundValidator(
                ClosureValidatorResolver::whenNotNullThen($bound, function (Time $resolved) {
                    return $this->createMinValidator($resolved, inclusive: false);
                })
            );

            return $this;
        }

        $this->getSupportsSecondaryValidation()->withValidator(
            $this->createMinValidator($bound, inclusive: false)
        );

        return $this;
    }

    public function before(Time|Field $bound): static
    {
        if ($bound instanceof Field) {
            $this->getSupportsSecondaryValidation()->withLateBoundValidator(
                ClosureValidatorResolver::whenNotNullThen($bound, function (Time $resolved) {
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

    public function between(Time $from, Time $to): static
    {
        $this->min($from);
        $this->max($to);

        return $this;
    }

    private function createMinValidator(Time $min, bool $inclusive): MinValidator
    {
        return new MinValidator(
            $min,
            inclusive: $inclusive,
            normalizer: fn(Time $time) => $time->totalSeconds(),
            formatter: fn(Time $time) => $time->formatWithSeconds(),
        );
    }

    private function createMaxValidator(Time $max, bool $inclusive): MaxValidator
    {
        return new MaxValidator(
            $max,
            inclusive: $inclusive,
            normalizer: fn(Time $time) => $time->totalSeconds(),
            formatter: fn(Time $time) => $time->formatWithSeconds(),
        );
    }
}