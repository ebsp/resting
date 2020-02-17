<?php

namespace Seier\Resting\Rules;

use Illuminate\Validation\Factory;
use Illuminate\Contracts\Validation\Rule;

abstract class BaseRule implements Rule
{
    protected function getRequest()
    {
        return app('request');
    }

    /**
     * @return Factory
     */
    protected function getValidator()
    {
        return app('restingValidator');
    }
}
