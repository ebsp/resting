<?php

namespace Seier\Resting\Fields;

class NumberField extends FieldAbstract
{
    protected $value = 0;

    protected function fieldValidation() : array
    {
        return ['numeric'];
    }

    public function type() : array
    {
        return [
            'type' => 'number',
            'format' => 'float',
        ];
    }
}