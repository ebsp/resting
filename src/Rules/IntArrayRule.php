<?php

namespace Seier\Resting\Rules;

class IntArrayRule extends BaseRule
{
    protected $messages;

    public function passes($attribute, $values)
    {
        if (! is_array($values)) {
            return ! $this->messages = [
                'must_be_array',
            ];
        }

        foreach ($values as $value) {
            if (! is_int($value)) {
                return ! $this->messages = [
                    'array_mixed_types'
                ];
            }
        }

        return !is_array($this->messages);
    }

    public function message()
    {
        return $this->messages;
    }
}
