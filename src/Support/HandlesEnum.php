<?php

namespace Seier\Resting\Support;

use Seier\Resting\Exceptions\InvalidEnumException;
use Seier\Resting\Exceptions\InvalidEnumOptionsException;

trait HandlesEnum
{
    protected $options;

    public function __construct(...$options)
    {
        if (is_array($options[0]) && 1 === func_num_args()) {
            $this->options = $options[0];
            return;
        } elseif (is_array($options[0])) {
            throw new InvalidEnumOptionsException;
        }

        $this->options = $options;
    }

    public function isValidType($value)
    {
        return is_string($value) || is_int($value);
    }

    public function isValidOption($value)
    {
        return in_array($value, $this->options());
    }

    public function options() : array
    {
        return $this->options;
    }

    protected function invalidOption($value)
    {
        return new InvalidEnumException('Enum value \'' . $value . '\' not valid');
    }

    protected function invalidType($value)
    {
        return new InvalidEnumException('Enum value must be a string');
    }
}
