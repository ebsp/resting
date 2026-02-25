<?php


namespace Seier\Resting\Validation\Secondary;


use Carbon\CarbonInterface;
use Seier\Resting\Fields\Field;
use Seier\Resting\Fields\CarbonField;
use Seier\Resting\Validation\Resolver\ClosureValidatorResolver;
use Seier\Resting\Validation\Secondary\Comparable\MinValidator;
use Seier\Resting\Validation\Secondary\Comparable\MaxValidator;

trait CarbonValidation
{

    protected abstract function getSupportsSecondaryValidation(): SupportsSecondaryValidation;

    public function min(CarbonInterface|Field $min): static
    {
        if ($min instanceof Field) {
            $this->getSupportsSecondaryValidation()->withLateBoundValidator(
                ClosureValidatorResolver::whenNotNullThen($min, function (CarbonInterface $resolved) {
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

    public function max(CarbonInterface|Field $max): static
    {
        if ($max instanceof Field) {
            $this->getSupportsSecondaryValidation()->withLateBoundValidator(
                ClosureValidatorResolver::whenNotNullThen($max, function (CarbonInterface $resolved) {
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

    public function after(CarbonInterface|Field $low): static
    {
        if ($low instanceof Field) {
            $this->getSupportsSecondaryValidation()->withLateBoundValidator(
                ClosureValidatorResolver::whenNotNullThen($low, function (CarbonInterface $resolved) {
                    return $this->createMinValidator($resolved, inclusive: false);
                })
            );

            return $this;
        }

        $this->getSupportsSecondaryValidation()->withValidator(
            $this->createMinValidator($low, inclusive: false)
        );

        return $this;
    }

    public function before(CarbonInterface|Field $high): static
    {
        if ($high instanceof CarbonField) {
            $this->getSupportsSecondaryValidation()->withLateBoundValidator(
                ClosureValidatorResolver::whenNotNullThen($high, function (CarbonInterface $resolved) {
                    return $this->createMaxValidator($resolved, inclusive: false);
                })
            );

            return $this;
        }

        $this->getSupportsSecondaryValidation()->withValidator(
            $this->createMaxValidator($high, inclusive: false)
        );

        return $this;
    }

    public function between(CarbonInterface $from, CarbonInterface $to): static
    {
        $this->min($from);
        $this->max($to);

        return $this;
    }

    private function createMinValidator(CarbonInterface $min, bool $inclusive): MinValidator
    {
        return new MinValidator(
            $min,
            inclusive: $inclusive,
            normalizer: fn(CarbonInterface $carbon) => $carbon->unix(),
            formatter: fn(CarbonInterface $carbon) => $carbon->toDateString(),
        );
    }

    private function createMaxValidator(CarbonInterface $max, bool $inclusive): MaxValidator
    {
        return new MaxValidator(
            $max,
            inclusive: $inclusive,
            normalizer: fn(CarbonInterface $carbon) => $carbon->unix(),
            formatter: fn(CarbonInterface $carbon) => $carbon->toDateString(),
        );
    }
}