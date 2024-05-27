<?php

namespace Seier\Resting\Validation\Predicates;

use Closure;
use Seier\Resting\Fields\Field;
use Seier\Resting\Support\ValueFormatter;

function whenProvided(Field...$fields): Predicate
{
    return AnonymousPredicate::of(
        function (ResourceContext $context) use ($fields) {
            $fieldNames = join(',', $context->getNames(...$fields));
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
            $fieldNames = join(',', $context->getNames(...$fields));
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
            $fieldNames = join(',', $context->getNames(...$fields));
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
            $fieldNames = join(',', $context->getNames(...$fields));
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
            $formatter = ValueFormatter::instance();
            return "True when value provided to $fieldName equals {$formatter->format($expected)}.";
        },
        function (ResourceContext $context) use ($expected, $field) {

            if (is_string($context->getRawValue($field))) {
                return $context->canBeParsed($field) && $context->getValue($field) === $expected;
            }

            return $context->getValue($field) === $expected;
        });
}

function whenNotEquals(Field $field, $notExpected): Predicate
{
    return AnonymousPredicate::of(
        function (ResourceContext $context) use ($notExpected, $field) {
            $fieldName = $context->getName($field);
            $formatter = ValueFormatter::instance();
            return "True when value provided to $fieldName does not equal {$formatter->format($notExpected)}.";
        },
        function (ResourceContext $context) use ($notExpected, $field) {

            if (is_string($context->getRawValue($field))) {
                return !$context->canBeParsed($field) || $context->getValue($field) !== $notExpected;
            }

            return $context->getValue($field) !== $notExpected;
        });
}

function whenIn(Field $field, array $oneOf): Predicate
{
    return AnonymousPredicate::of(
        function (ResourceContext $context) use ($oneOf, $field) {
            $fieldName = $context->getName($field);
            $formatter = ValueFormatter::instance();
            return "True when value provided to $fieldName is one of {$formatter->format($oneOf, showType: false)}.";
        },
        function (ResourceContext $context) use ($oneOf, $field) {

            if (is_string($context->getRawValue($field))) {
                return $context->canBeParsed($field) && in_array($context->getValue($field), $oneOf, strict: true);
            }

            return in_array($context->getValue($field), $oneOf, strict: true);
        });
}


function whenNotIn(Field $field, array $oneOf): Predicate
{
    return AnonymousPredicate::of(
        function (ResourceContext $context) use ($oneOf, $field) {
            $fieldName = $context->getName($field);
            $formatter = ValueFormatter::instance();
            return "True when value provided to $fieldName is none of {$formatter->format($oneOf, showType: false)}.";
        },
        function (ResourceContext $context) use ($oneOf, $field) {

            if (is_string($context->getRawValue($field))) {
                return !$context->canBeParsed($field) || !in_array($context->getValue($field), $oneOf, strict: true);
            }

            return !in_array($context->getValue($field), $oneOf, strict: true);
        });
}

function whenPasses(Field $field, Closure $closure): Predicate
{
    return AnonymousPredicate::of(
        function (ResourceContext $context) use ($field) {
            $fieldName = $context->getName($field);
            return "True when value provided to $fieldName passes a custom closure validator.";
        },
        function (ResourceContext $context) use ($closure, $field) {
            return $closure($context->getValue($field), $context);
        },
    );
}

function any(array $predicates): Predicate
{
    return AnonymousPredicate::of(
        function (ResourceContext $context) use ($predicates) {
            $message = "True when any of the following are true:";
            foreach ($predicates as $predicate) {
                $message .= " - " . $predicate->description($context);
            }

            return $message;
        },
        function (ResourceContext $context) use ($predicates) {
            foreach ($predicates as $predicate) {
                if ($predicate->passes($context)) {
                    return true;
                }
            }

            return false;
        }
    );
}

function all(array $predicates): Predicate
{
    return AnonymousPredicate::of(
        function (ResourceContext $context) use ($predicates) {
            $message = "True when all of the following are true:";
            foreach ($predicates as $predicate) {
                $message .= " - " . $predicate->description($context);
            }

            return $message;
        },
        function (ResourceContext $context) use ($predicates) {
            foreach ($predicates as $predicate) {
                if (!$predicate->passes($context)) {
                    return false;
                }
            }

            return true;
        }
    );
}

function none(array $predicates): Predicate
{
    return AnonymousPredicate::of(
        function (ResourceContext $context) use ($predicates) {
            $message = "True when all of the following are true:";
            foreach ($predicates as $predicate) {
                $message .= " - " . $predicate->description($context);
            }

            return $message;
        },
        function (ResourceContext $context) use ($predicates) {
            foreach ($predicates as $predicate) {
                if ($predicate->passes($context)) {
                    return false;
                }
            }

            return true;
        }
    );
}