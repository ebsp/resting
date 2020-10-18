<?php

namespace Seier\Resting\Fields;

class IntField extends FieldAbstract
{
    protected $value = 0;

    protected function setMutator($value)
    {
        if (is_int($value) || is_null($value)) {
            return $value;
        }

        if (is_numeric($value) || (is_string($value) && preg_match('/^[0-9]+$/', $value))) {
            return (int)$value;
        }

        return $value;
    }

    protected function fieldValidation(): array
    {
        return ['int'];
    }

    public function type(): array
    {
        return [
            'type' => 'integer',
            'format' => 'int64',
        ];
    }
}