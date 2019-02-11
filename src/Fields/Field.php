<?php

namespace Seier\Resting\Fields;

class Field extends FieldAbstract
{
    protected function getMutator($value)
    {
        return $value;
    }

    protected function setMutator($value)
    {
        return $value;
    }

    protected function fieldValidation() : array
    {
        return [];
    }

    public function type() : array
    {
        return [];
    }
}