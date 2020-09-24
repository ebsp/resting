<?php

namespace Seier\Resting\Fields;

class IntField extends FieldAbstract
{
    protected $value;

    public function getMutator($value)
    {
        return is_null($value) ? $value : (int)$value;
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