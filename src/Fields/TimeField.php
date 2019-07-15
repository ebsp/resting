<?php

namespace Seier\Resting\Fields;

use Seier\Resting\Exceptions\InvalidTimeFormatException;

class TimeField extends FieldAbstract
{
    protected $withSeconds = true;

    public function withSeconds(bool $withSeconds)
    {
        $this->withSeconds = $withSeconds;

        return $this;
    }

    public function setMutator($value)
    {
        $secondsPattern = $this->withSeconds ? ':([0-5]?[0-9])' : '';
        $pattern = "/^(2[0-3]|[01]?[0-9]):([0-5]?[0-9]){$secondsPattern}$/";

        if (! preg_match($pattern, $value)) {
            $this->error(new InvalidTimeFormatException);
        }

        return $value;
    }

    protected function fieldValidation() : array
    {
        return ['date_format:"H:i' . ($this->withSeconds ? ':s' : '') . '"'];
    }

    public function type() : array
    {
        return [
            'type' => 'string',
            'format' => 'time',
        ];
    }
}
