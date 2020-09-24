<?php

namespace Seier\Resting\Fields;

class IntField extends FieldAbstract
{
    protected $value = 0;

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