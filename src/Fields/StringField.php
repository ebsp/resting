<?php

namespace Seier\Resting\Fields;

use Seier\Resting\Exceptions\InvalidTypeException;

class StringField extends FieldAbstract
{
    protected $value = '';

    protected function setMutator($value)
    {
        if (! is_string($value)) {
            $this->error(new InvalidTypeException('Value must be string'));
        }

        return $value;
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
