<?php

namespace Seier\Resting\Rules;

use Illuminate\Support\Arr;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Support\Arrayable;
use Seier\Resting\Tests\Support\HandlesEnum;
use Seier\Resting\Exceptions\InvalidEnumOptionsException;

class EnumArrayRule implements Rule
{
    use HandlesEnum;

    public function passes($attribute, $values)
    {
        if (! Arr::accessible($values)) {
            return false;
        }

        $values = ($values instanceof Arrayable) ? $values->toArray() : $values;

        foreach ($values as $value) {
            if (! $this->isValidType($value) || ! $this->isValidOption($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string|array
     */
    public function message()
    {
        return 'invalid_enum';
    }
}
