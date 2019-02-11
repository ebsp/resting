<?php

namespace Seier\Resting\Fields;

use Illuminate\Support\Carbon;

class CarbonField extends FieldAbstract
{
    protected function getMutator($value)
    {
        return $value;
    }

    protected function setMutator($value)
    {
        if (! $value instanceof Carbon) {
            $value = Carbon::parse($value);
        }

        /** @var $value Carbon */
        return $value->toIso8601ZuluString();
    }

    protected function fieldValidation() : array
    {
        return ['date'];
    }

    public function type() : array
    {
        return [
            'type' => 'string',
            'format' => 'date-time',
        ];
    }
}