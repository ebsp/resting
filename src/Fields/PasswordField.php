<?php

namespace Seier\Resting\Fields;

class PasswordField extends StringField
{
    public function type() : array
    {
        return [
            'type' => 'string',
            'format' => 'password',
        ];
    }
}
