<?php

namespace Seier\Resting\Fields;

use Exception;
use Illuminate\Support\Carbon;
use Seier\Resting\Rules\DatePeriodRule;
use Seier\Resting\Exceptions\InvalidPeriodException;
use Seier\Resting\Exceptions\PeriodExceedsRangeException;

class DatePeriodStringField extends FieldAbstract
{
    protected $maxRangeInDays;

    public function __construct(int $maxRangeInDays = null)
    {
        $this->maxRangeInDays = $maxRangeInDays;
    }

    public function getMutator($value)
    {
        return $value;
    }

    public function setMutator($value)
    {
        if (! is_string($value)) {
            $this->error(new InvalidPeriodException('Period value must be string'));
        }

        $period = explode(',', $value);
        $values = [];
        $to = null;

        try {
            if (! $from = $this->dateIsValid($period[0])) {
                $this->error(new InvalidPeriodException('Period has invalid date'));
            }

            if (count($period) === 2) {
                if (! $to = $this->dateIsValid($period[1])) {
                    $this->error(new InvalidPeriodException('Period has invalid date'));
                }
            }

            $values = [
                $from, $to
            ];
        } catch (Exception $exception) {
            $this->error(new InvalidPeriodException('Period has invalid date'));
        }

        if (count($period) === 2 && $this->maxRangeInDays && $values[0]->diffInDays($values[1]) > $this->maxRangeInDays) {
            $this->error(new PeriodExceedsRangeException('Period exceeds range'));
        }

        if (count($period) === 2 && $values[0]->gt($values[1])) {
            $this->error(new InvalidPeriodException('Period ends before it starts'));
        }

        return $values;
    }

    protected function fieldValidation() : array
    {
        return [new DatePeriodRule(
            $this->maxRangeInDays
        )];
    }

    protected function dateIsValid(string $dateString)
    {
        $parsed = Carbon::createFromFormat('Y-m-d', $dateString)->startOfDay();
        return $parsed->format('Y-m-d') === $dateString ? $parsed : false;
    }

    public function type() : array
    {
        return [
            'type' => 'string',
        ];
    }
}
