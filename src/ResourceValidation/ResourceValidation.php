<?php

namespace Seier\Resting\ResourceValidation;

use Seier\Resting\Fields\Field;
use Seier\Resting\Validation\Predicates\Predicate;

trait ResourceValidation
{
    private array $validators = [];

    public function getResourceValidators(): array
    {
        return $this->validators;
    }

    public function addResourceValidator(ResourceValidator $validator): static
    {
        $this->validators[] = $validator;

        return $this;
    }

    public function lessThan(
        Field|int|string|bool|float|array $left,
        Field|int|string|bool|float|array $right,
    ): static
    {
        $this->validators[] = new ResourceAttributeComparisonValidator(
            resource: $this,
            operator: ResourceAttributeComparisonOperator::LessThan,
            left: is_array($left) ? $left : [$left],
            right: is_array($right) ? $right : [$right],
        );

        return $this;
    }

    public function greaterThan(
        Field|int|string|bool|float|array $left,
        Field|int|string|bool|float|array $right,
    ): static
    {
        $this->validators[] = new ResourceAttributeComparisonValidator(
            resource: $this,
            operator: ResourceAttributeComparisonOperator::GreaterThan,
            left: is_array($left) ? $left : [$left],
            right: is_array($right) ? $right : [$right],
        );

        return $this;
    }

    public function equal(
        Field|int|string|bool|float|array $left,
        Field|int|string|bool|float|array $right,
    ): static
    {
        $this->validators[] = new ResourceAttributeComparisonValidator(
            resource: $this,
            operator: ResourceAttributeComparisonOperator::Equal,
            left: is_array($left) ? $left : [$left],
            right: is_array($right) ? $right : [$right],
        );

        return $this;
    }

    public function lessThanOrEqual(
        Field|int|string|bool|float|array $left,
        Field|int|string|bool|float|array $right,
    ): static
    {
        $this->validators[] = new ResourceAttributeComparisonValidator(
            resource: $this,
            operator: ResourceAttributeComparisonOperator::LessThanOrEqual,
            left: is_array($left) ? $left : [$left],
            right: is_array($right) ? $right : [$right],
        );

        return $this;
    }

    public function greaterThanOrEqual(
        Field|int|string|bool|float|array $left,
        Field|int|string|bool|float|array $right,
    ): static
    {
        $this->validators[] = new ResourceAttributeComparisonValidator(
            resource: $this,
            operator: ResourceAttributeComparisonOperator::GreaterThanOrEqual,
            left: is_array($left) ? $left : [$left],
            right: is_array($right) ? $right : [$right],
        );

        return $this;
    }
}