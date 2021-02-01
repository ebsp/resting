<?php


namespace Seier\Resting\Validation;


interface Validator
{

    public function description(): string;

    public function validate(mixed $value): array;
}