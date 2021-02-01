<?php

namespace Seier\Resting\Validation\Predicates;

use Seier\Resting\Fields\Field;

function whenProvided(Field $field): Predicate
{
    return AnonymousPredicate::of(
        function (ResourceContext $context) use ($field) {
            $fieldName = $context->getName($field);
            return "True when $fieldName was provided.";
        },
        function (ResourceContext $context) use ($field) {
            return $context->wasProvided($field);
        });
}

function whenNotProvided(Field $field): Predicate
{
    return AnonymousPredicate::of(
        function (ResourceContext $context) use ($field) {
            $fieldName = $context->getName($field);
            return "True when $fieldName was not provided.";
        },
        function (ResourceContext $context) use ($field) {
            return !$context->wasProvided($field);
        });
}

function whenNull(Field $field): Predicate
{
    return AnonymousPredicate::of(

        function (ResourceContext $context) use ($field) {
            $fieldName = $context->getName($field);
            return "True when $fieldName is null.";
        },
        function (ResourceContext $context) use ($field) {
            return $context->isNull($field);
        });
}

function whenNotNull(Field $field): Predicate
{
    return AnonymousPredicate::of(
        function (ResourceContext $context) use ($field) {
            $fieldName = $context->getName($field);
            return "True when $fieldName is not null.";
        },
        function (ResourceContext $context) use ($field) {
            return !$context->isNull($field);
        });
}

function whenEquals(Field $field, $expected): Predicate
{
    return AnonymousPredicate::of(
        function (ResourceContext $context) use ($expected, $field) {
            $fieldName = $context->getName($field);
            return "True when value provided to $fieldName is equals $expected.";
        },
        function (ResourceContext $context) use ($expected, $field) {

            if (is_string($context->getRawValue($field))) {
                return $context->canBeParsed($field) && $context->getValue($field) === $expected;
            }

            return $context->getValue($field) === $expected;
        });
}
