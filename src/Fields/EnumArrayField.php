<?php

namespace Seier\Resting\Fields;

use Illuminate\Support\Arr;
use Seier\Resting\Rules\EnumArrayRule;
use Seier\Resting\Support\HandlesEnum;
use Illuminate\Contracts\Support\Arrayable;
use Seier\Resting\Exceptions\NotArrayException;

class EnumArrayField extends FieldAbstract
{
    use HandlesEnum;

    protected $value = [];

    public function push($value)
    {
        $this->value = $this->setMutator(array_merge($this->value, [$value]));

        return $this;
    }

    protected function setMutator($value)
    {
        if (Arr::accessible($value)) {
            $values = ($value instanceof Arrayable) ? $value->toArray() : $value;

            foreach ($values as $value) {
                if (! $this->isValidType($value)) {
                    $this->error($this->invalidType($value));
                }

                if (! $this->isValidOption($value)) {
                    $this->error($this->invalidOption($value));
                }
            }
        } elseif (is_null($value) && $this->isNullable()) {
            return $value;
        } else {
            $values = $value;
            $this->error(new NotArrayException('Field value is not an array'));
        }

        return $values;
    }

    protected function fieldValidation() : array
    {
        return ['array', new EnumArrayRule($this->options())];
    }

    public function type() : array
    {
        return [
            'type' => 'array',
            'items' => [],
        ];
    }
}
