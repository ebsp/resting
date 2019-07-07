<?php

namespace Seier\Resting\Rules;

use Seier\Resting\Resource;
use Illuminate\Validation\Factory;
use Illuminate\Contracts\Validation\Rule;

class ResourceArrayRule implements Rule
{
    protected $resource;
    protected $resources = 0;
    protected $required;
    protected $messages = [];

    public function __construct(Resource $resource)
    {
        $this->resource = $resource;
    }

    public function passes($attribute, $values)
    {
        if (! is_array($values)) {
            return false;
        }

        $i = 0;

        foreach ($values as $value) {
            $resourceValidator = $this->getValidator()->make(
                $value->toArray(),
                $value->validation(
                    $this->getRequest()
                )
            );

            if ($resourceValidator->fails()) {
                $this->messages["_" . $i] = $resourceValidator->errors()->toArray();
            }

            $i++;
        }

        return ! count($this->messages);
    }

    public function message()
    {
        return $this->messages;
    }

    public function resource()
    {
        return $this->resource;
    }

    /**
     * @return Factory
     */
    protected function getValidator()
    {
        return app('restingValidator');
    }

    protected function getRequest()
    {
        return app('request');
    }
}
