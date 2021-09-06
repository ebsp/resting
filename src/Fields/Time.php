<?php


namespace Seier\Resting\Fields;


use Carbon\Carbon;

class Time
{

    public int $hours;
    public int $minutes;
    public int $seconds;

    public function __construct(int $hours = 0, int $minutes = 0, int $seconds = 0)
    {
        $this->hours = $hours;
        $this->minutes = $minutes;
        $this->seconds = $seconds;
    }

    public static function zeroes(): Time
    {
        return new Time();
    }

    public function format(string $format): string
    {
        return $this->toCarbon()->format($format);
    }

    public function formatWithoutSeconds(): string
    {
        return $this->format('H:i');
    }

    public function formatWithSeconds(): string
    {
        return $this->format('H:i:s');
    }

    public function totalSeconds(): int
    {
        return (
            $this->hours * 60 * 60 +
            $this->minutes * 60 +
            $this->seconds
        );
    }

    private function toCarbon(): Carbon
    {
        return Carbon::create(
            hour: $this->hours,
            minute: $this->minutes,
            second: $this->seconds,
        );
    }

    public static function fromCarbon(Carbon $carbon): static
    {
        return new static(
            hours: $carbon->hour,
            minutes: $carbon->minute,
            seconds: $carbon->second,
        );
    }
}