<?php

namespace Seier\Resting\Fields;

use Illuminate\Support\Carbon;

class CarbonField extends FieldAbstract
{
    public function getMutator($value):? Carbon
    {
        if (! $this->isNullable() && is_null($value)) {
            return new Carbon;
        }

        return $value;
    }

    public function setMutator($value):? Carbon
    {
        if (! $value instanceof Carbon && ! is_null($value)) {
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
