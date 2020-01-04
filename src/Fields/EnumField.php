<?php

namespace Seier\Resting\Fields;

use Seier\Resting\Support\HandlesEnum;

class EnumField extends StringField
{
    use HandlesEnum;

    protected function setMutator($value)
    {
        if (is_null($value) && $this->isNullable()){
            return $value;
        } elseif (! $this->isValidType($value)) {
            $this->error($this->invalidType($value));
        }
        elseif (! $this->isValidOption($value)) {
            $this->error($this->invalidOption($value));
        }

        return $value;
    }

    protected function fieldValidation() : array
    {
        return [
            'in:' . implode(',', $this->options())
        ];
    }

    public function type() : array
    {
        return [
            'type' => 'string',
            'enum' => $this->options(),
        ];
    }
}
