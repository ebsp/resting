<?php

namespace Seier\Resting\Fields;

use Seier\Resting\Exceptions\InvalidEnumException;
use Seier\Resting\Exceptions\InvalidEnumOptionsException;

class EnumField extends StringField
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

    protected function setMutator($value)
    {
        if (! $this->isValid($value)) {
            $this->error(
                new InvalidEnumException('Enum value \''. $value .'\' not valid')
            );
        }

        return $value;
    }

    protected function fieldValidation() : array
    {
        return [
            'in:' . implode(',', $this->options())
        ];
    }

    public function options() : array
    {
        return $this->options;
    }

    public function isValid($value)
    {
        return in_array($value, $this->options());
    }

    public function type() : array
    {
        return [
            'type' => 'string',
            'enum' => $this->options(),
        ];
    }
}
