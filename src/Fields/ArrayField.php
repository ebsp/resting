<?php

namespace Seier\Resting\Fields;

use Illuminate\Support\Arr;
use Illuminate\Contracts\Support\Arrayable;
use Seier\Resting\Exceptions\NotArrayException;

class ArrayField extends FieldAbstract
{
    protected $value = [];

    public function push($value)
    {
        $this->value[] = $value;

        return $this;
    }

    protected function setMutator($value)
    {
        if (Arr::accessible($value)) {
            return ($value instanceof Arrayable) ? $value->toArray() : $value;
        } else {
            throw new NotArrayException('Field value is not an array');
        }
    }

    protected function fieldValidation() : array
    {
        return ['array'];
    }

    public function type() : array
    {
        return [
            'type' => 'array',
            'items' => [],
        ];
    }
}
