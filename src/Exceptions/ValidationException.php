<?php

namespace Seier\Resting\Exceptions;

use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException as BaseValidationException;
use stdClass;

class ValidationException extends BaseValidationException
{
    public function errors()
    {
        $messages = [];

        foreach ($this->validator->errors()->messages() as $key => $value) {
            Arr::set($messages, $key, $value);
        }

        if (isset($messages['body'])) {
            foreach ($messages['body'] as $key => $value) {
                if (is_int($key) && is_array($value)) {
                    unset($messages['body'][$key]);
                    $messages['body']['_' . $key] = $value;
                }
            }
        }

        return $messages;
    }
}
