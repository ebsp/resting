<?php

namespace Seier\Resting\Rules;

use Seier\Resting\Resource;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class ResourceRule implements Rule
{
    protected $resource;
    protected $required;
    protected $validation;

    public function __construct(
        Resource $resource,
        $overwriteRequirements = false
    ) {
        $this->resource = $resource;

        $this->validation = Validator::make(
            $this->resource->toArray(),
            $this->resource->validation(
                request(), $overwriteRequirements
            )
        );
    }

    public function passes($attribute, $value)
    {
        return ! $this->validation->errors()->any();
    }

    public function message()
    {
        return $this->validation->errors()->toArray();
    }
}
