<?php

namespace Seier\Resting\Rules;

use Illuminate\Contracts\Validation\Rule;
use Seier\Resting\Resource;

class ResourceArrayRule implements Rule
{
    protected $resource;
    protected $resources = 0;
    protected $required;
    protected $messages = [];

    public function __construct(
        Resource $resource,
        $required = false
    ) {
        $this->resource = $resource;
        $this->required = $required;
    }

    public function passes($attribute, $values)
    {
        $this->resources = $values ?? 0;

        if (! is_array($values)) {
            return false;
        }

        $i=0;

        foreach ($values as $value) {
            $class = get_class($this->resource);
            $resource = $class::fromArray($value);

            $resourceValidator = validator([
                'resource' => $value
            ], [
                'resource' => new ResourceRule($resource, true)
            ]);

            if ($resourceValidator->fails()) {
                $this->messages["_" . $i] = $resourceValidator->errors()->toArray()['resource'][0];
            }

            $i++;
        }

        return ! count($this->messages);
    }

    public function message()
    {
        return $this->messages;
    }
}
