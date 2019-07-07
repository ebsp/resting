<?php

namespace Seier\Resting\Fields;

class BoolField extends FieldAbstract
{
    public function getMutator($value)
    {
        return (bool) $value;
    }

    public function setMutator($value)
    {
        return (bool) $value;
    }

    protected function fieldValidation() : array
    {
        return ['bool'];
    }

    public function type() : array
    {
        return [
            'type' => 'boolean',
        ];
    }
}