<?php

namespace Seier\Resting\Fields;

use Illuminate\Support\Carbon;

class CarbonField extends FieldAbstract
{
    public function getMutator($value): ?Carbon
    {
        if (!$this->isNullable() && is_null($value)) {
            return new Carbon;
        }

        return $value;
    }

    public function setMutator($value): ?Carbon
    {
        if (!$value instanceof Carbon && !is_null($value)) {
            try {
                $value = Carbon::parse($value);
            } catch (\Exception $e) {
                $value = null;
            }
        }

        return $value;
    }

    public function formatted()
    {
        return optional($this->get())->toIso8601String();
    }

    protected function fieldValidation(): array
    {
        return $this->required && !$this->nullable ? ['valid_timestamp:required'] : ['valid_timestamp:nullable'];
    }

    public function type(): array
    {
        return [
            'type' => 'string',
            'format' => 'date-time',
        ];
    }
}
