<?php

namespace Seier\Resting\Fields;

use Illuminate\Support\Arr;
use Seier\Resting\Rules\IntArrayRule;
use Illuminate\Contracts\Support\Arrayable;
use Seier\Resting\Exceptions\NotArrayException;
use Seier\Resting\Exceptions\InvalidTypeException;

class IntArrayField extends FieldAbstract
{
    protected $value = [];

    public function push($value)
    {
        return $this->set(array_merge($this->value, [$value]));
    }

    protected function setMutator($value)
    {
        if (Arr::accessible($value)) {
            $values = ($value instanceof Arrayable) ? $value->toArray() : $value;

            foreach ($values as $value) {
                if (! $this->isValidType($value)) {
                    $this->error($this->invalidType($value));
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

    protected function isValidType($value)
    {
        return is_int($value);
    }

    protected function invalidType($value)
    {
        return new InvalidTypeException('Value must be an int');
    }

    protected function fieldValidation() : array
    {
        return ['array', new IntArrayRule];
    }

    public function type() : array
    {
        return [
            'type' => 'array',
            'items' => [
                'type' => 'integer',
            ],
        ];
    }
}
