<?php


namespace Seier\Resting\Validation\Secondary;


use Seier\Resting\Validation\Validator;

interface SecondaryValidator extends Validator
{

    public function isUnique(): bool;
}