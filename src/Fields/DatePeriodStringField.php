<?php

namespace Seier\Resting\Fields;

use Seier\Resting\Exceptions\InvalidPeriodException;

class DatePeriodStringField extends DatePeriodField
{
    protected function parseValue($value)
    {
        if (! is_string($value)) {
            $this->error(new InvalidPeriodException('Period value must be a string'));
            return null;
        }

        return explode(',', $value);
    }

    public function type() : array
    {
        return [
            'type' => 'string',
        ];
    }
}
