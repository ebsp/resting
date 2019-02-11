<?php

namespace Seier\Resting\Fields;

class StringField extends FieldAbstract
{
    protected $value = '';

    protected function setMutator($value)
    {
        return (string) $value;
    }

    protected function fieldValidation() : array
    {
        return ['string'];
    }

    public function type() : array
    {
        return [
            'type' => 'string',
        ];
    }
}
