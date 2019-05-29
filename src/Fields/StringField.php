<?php

namespace Seier\Resting\Fields;

class StringField extends FieldAbstract
{
    protected $value = '';

    protected function setMutator($value)
    {
        return (string) $value;
    }

    public function formatted()
    {
        return ($this->nullable && ! $this->filled() && ! strlen($this->value)) ? null : $this->value;
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
