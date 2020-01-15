<?php

namespace Seier\Resting\Rules;

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
            'period_starts' => $values[0],
            'period_ends' => $values[1] ?? null,
        ], [
            'period_starts' => 'required|date',
            'period_ends' => 'date|nullable|after_or_equal:period_starts'
        ]);

        if ($fails = $validator->errors()->any()) {
            $this->messages = $validator->errors()->toArray();
        }

        return ! $fails;
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
