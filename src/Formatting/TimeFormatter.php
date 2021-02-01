<?php


namespace Seier\Resting\Formatting;


use Carbon\Carbon;
use Seier\Resting\Fields\Time;
use Seier\Resting\Validation\Secondary\Panics;

class TimeFormatter implements Formatter
{

    use Panics;

    private ?string $format = null;

    public function format(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Time) {
            $this->panic();
        }

        $format = $this->format ?: 'H:i:s';
        $carbon = Carbon::create(
            hour: $value->hours,
            minute: $value->minutes,
            second: $value->seconds
        );

        return $carbon->format($format);
    }

    public function withFormat(string $format): static
    {
        $this->format = $format;

        return $this;
    }
}