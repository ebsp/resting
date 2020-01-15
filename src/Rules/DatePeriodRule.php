<?php

namespace Seier\Resting\Rules;

use Carbon\Carbon;
use Illuminate\Validation\Factory;
use Illuminate\Contracts\Validation\Rule;

class DatePeriodRule implements Rule
{
    protected $maxRangeInDays;
    protected $value;

    protected $messages;
    protected $validation;

    public function __construct(int $maxRangeInDays = null)
    {
        $this->maxRangeInDays = $maxRangeInDays;
    }

    public function passes($attribute, $values)
    {
        if (! is_array($values)) {
            return ! $this->messages = [
                'period_starts' => ['validation.date'],
            ];
        }

        $validator = $this->getValidator()->make([
            'period_starts' => $from = $values[0],
            'period_ends' => $to = $values[1] ?? null,
        ], [
            'period_starts' => 'required|date',
            'period_ends' => 'date|nullable|after_or_equal:period_starts'
        ]);

        if ($validator->errors()->any()) {
            $this->messages = $validator->errors()->toArray();
        }

        if ($this->maxRangeInDays && $from && $to && $from->diffInDays($to) > $this->maxRangeInDays) {
            $this->messages['period_ends'][] = 'validation.range_limit_exceeds';
        }

        return !is_array($this->messages);
    }

    public function message()
    {
        return $this->messages;
    }

    /**
     * @return Factory
     */
    protected function getValidator()
    {
        return app('restingValidator');
    }
}
