<?php

namespace Seier\Resting\Fields;

use Illuminate\Support\Carbon;

class CarbonField extends FieldAbstract
{
    protected function getMutator($value) : Carbon
    {
        return $value;
    }

    protected function setMutator($value) : Carbon
    {
        if (! $value instanceof Carbon) {
            $value = Carbon::parse($value);
        }

        /** @var $value Carbon */
        return $value;
    }

    public function formatted()
    {
        return optional($this->get())->toIso8601String();
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
