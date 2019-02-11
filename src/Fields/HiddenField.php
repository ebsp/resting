<?php

namespace Seier\Resting\Fields;

class HiddenField extends FieldAbstract
{
    protected $hidden = true;

    protected function fieldValidation(): array
    {
        return [];
    }

    public function type() : array
    {
        return [
            'type' => 'string',
        ];
    }
}
