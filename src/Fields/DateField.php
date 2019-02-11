<?php

namespace Seier\Resting\Fields;

use Illuminate\Support\Carbon;

class DateField extends FieldAbstract
{
    protected function getMutator($value)
    {
        return $value;
    }

    protected function setMutator($value)
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
