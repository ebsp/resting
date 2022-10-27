<?php

namespace Seier\Resting\Validation;

use Carbon\CarbonPeriod;
use Seier\Resting\Validation\Errors\ValidationError;
use Seier\Resting\Validation\Errors\NotCarbonPeriodValidationError;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;
use Seier\Resting\Validation\Errors\CarbonPeriodEndRequiredValidationError;
use Seier\Resting\Validation\Secondary\CarbonPeriod\CarbonPeriodValidation;
use Seier\Resting\Validation\Errors\CarbonPeriodOrderedRequiredValidationError;

class CarbonPeriodValidator extends BasePrimaryValidator implements PrimaryValidator
{
    use CarbonPeriodValidation;

    private bool $requireEnd = true;
    private bool $requireOrdered = true;
    private CarbonValidator $startValidator;
    private CarbonValidator $endValidator;

    public function __construct()
    {
        $this->startValidator = new CarbonValidator();
        $this->endValidator = new CarbonValidator();
    }

    public function description(): string
    {
        return "Expects the value to be a carbon period.";
    }

    public function validate(mixed $value): array
    {
        if (!$value instanceof CarbonPeriod) {
            return [new NotCarbonPeriodValidationError($value)];
        }

        if ($this->requireEnd && $value->end === null) {
            return [new CarbonPeriodEndRequiredValidationError()];
        }

        if ($this->requireOrdered && $value->end !== null) {
            if ($value->start > $value->end) {
                return [new CarbonPeriodOrderedRequiredValidationError(
                    $value->start,
                    $value->end,
                )];
            }
        }

        $errors = [];
        $errors = array_merge($errors, array_map(fn(ValidationError $e) => $e->prependPath('start'), $this->startValidator->validate($value->start)));
        $errors = array_merge($errors, array_map(fn(ValidationError $e) => $e->prependPath('end'), $value->end ? $this->endValidator->validate($value->end) : []));
        $errors = array_merge($errors, $this->runValidators($value));

        return $errors;
    }

    public function setStartValidator(CarbonValidator $startValidator): static
    {
        $this->startValidator = $startValidator;

        return $this;
    }

    public function setEndParser(CarbonValidator $endValidator): static
    {
        $this->endValidator = $endValidator;

        return $this;
    }

    public function onStart(callable $callable): static
    {
        $callable($this->startValidator);

        return $this;
    }

    public function onEnd(callable $callable): static
    {
        $callable($this->endValidator);

        return $this;
    }

    public function requireEnd(bool $state = true): static
    {
        $this->requireEnd = $state;

        return $this;
    }

    public function requireOrdered(bool $state = true): static
    {
        $this->requireOrdered = $state;

        return $this;
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        return $this;
    }
}