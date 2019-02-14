<?php

namespace Seier\Resting\Support;

use Illuminate\Validation\Validator;
use Seier\Resting\Rules\ResourceRule;
use Seier\Resting\Rules\ResourceArrayRule;

class RestValidator extends Validator
{
    protected function validateUsingCustomRule($attribute, $value, $rule)
    {
        if (! $rule->passes($attribute, $value)) {
            $this->failedRules[$attribute][get_class($rule)] = [];

            $messages = (array) $rule->message();

            if ($rule instanceof ResourceArrayRule || $rule instanceof ResourceRule) {
                $this->messages()->merge(
                    [$attribute => $messages],
                    $this->messages()->get($attribute)
                );
            } else {
                foreach ($messages as $message) {
                    $this->messages->add($attribute, $this->makeReplacements(
                        $message, $attribute, get_class($rule), []
                    ));
                }
            }
        }
    }
}
