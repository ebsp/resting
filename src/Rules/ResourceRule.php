<?php

namespace Seier\Resting\Rules;

use Seier\Resting\Resource;

class ResourceRule extends BaseRule
{
    protected $resource;
    protected $messages;
    protected $validation;
    protected $overwriteRequirements;

    public function __construct(Resource $resource, $overwriteRequirements = false)
    {
        $this->resource = $resource;
        $this->overwriteRequirements = $overwriteRequirements;
    }

    public function passes($attribute, $values)
    {
        $rules = $this->resource->validation(
            $this->getRequest(),
            $this->overwriteRequirements
        );

        $validator = $this->getValidator()->make(
            $values,
            $rules
        );

        if ($fails = $validator->errors()->any()) {
            $this->messages = $validator->errors()->toArray();
        }

        return ! $fails;
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
