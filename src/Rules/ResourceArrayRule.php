<?php

namespace Seier\Resting\Rules;

use Seier\Resting\Resource;

class ResourceArrayRule extends BaseRule
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
            if (is_array($value)) {
                // TODO: fix so this is not needed
                $value = $this->resource->copy()->setPropertiesFromCollection(collect($value));
            }

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
}
