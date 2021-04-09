<?php

namespace Seier\Resting\Validation\Predicates;

use Seier\Resting\Fields\Field;

function whenProvided(Field...$fields): Predicate
{
    return AnonymousPredicate::of(
        function (ResourceContext $context) use ($fields) {
            $fieldNames = join(',', $context->getNames($fields));
            return "True when $fieldNames is provided.";
        },
        function (ResourceContext $context) use ($fields) {
            foreach ($fields as $field) {
                if (!$context->wasProvided($field)) {
                    return false;
                }
            }

            return true;
        });
}

function whenNotProvided(Field...$fields): Predicate
{
    return AnonymousPredicate::of(
        function (ResourceContext $context) use ($fields) {
            $fieldNames = join(',', $context->getNames($fields));
            return "True when $fieldNames is not provided.";
        },
        function (ResourceContext $context) use ($fields) {
            foreach ($fields as $field) {
                if ($context->wasProvided($field)) {
                    return false;
                }
            }

            return true;
        });
}

function whenNull(Field...$fields): Predicate
{
    return AnonymousPredicate::of(
        function (ResourceContext $context) use ($fields) {
            $fieldNames = join(',', $context->getNames($fields));
            return "True when $fieldNames is null.";
        },
        function (ResourceContext $context) use ($fields) {
            foreach ($fields as $field) {
                if (!$context->isNull($field)) {
                    return false;
                }
            }

            return true;
        });
}

function whenNotNull(Field...$fields): Predicate
{
    return AnonymousPredicate::of(
        function (ResourceContext $context) use ($fields) {
            $fieldNames = join(',', $context->getNames($fields));
            return "True when $fieldNames is not null.";
        },
        function (ResourceContext $context) use ($fields) {
            foreach ($fields as $field) {
                if ($context->isNull($field)) {
                    return false;
                }
            }

            return true;
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
