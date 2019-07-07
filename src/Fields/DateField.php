<?php

namespace Seier\Resting\Fields;

use Illuminate\Support\Carbon;

class DateField extends FieldAbstract
{
    public function getMutator($value)
    {
        return $value;
    }

    public function setMutator($value)
    {
        $value = $value ? Carbon::parse($value) : null;

        /** @var $value Carbon */
        return optional($value)->format('Y-m-d');
    }

    protected function fieldValidation() : array
    {
        return ['date'];
    }

    public function type() : array
    {
        return [
            'type' => 'string',
            'format' => 'date',
        ];
    }
}
