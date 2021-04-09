<?php


namespace Seier\Resting\Validation\Predicates;


use Seier\Resting\Fields\Field;

interface ResourceContext
{

    public function getName(Field $field): string;

    public function getNames(Field ...$fields): array;

    public function wasProvided(Field $field): bool;

    public function isNull(Field $field): bool;

    public function canBeParsed(Field $field): bool;

    public function getValue(Field $field): mixed;

    public function getRawValue(Field $field): mixed;
}