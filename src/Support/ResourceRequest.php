<?php

namespace Seier\Resting\Support;

use Seier\Resting\Resource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ResourceRequest extends FormRequest
{
    protected $redirect = null;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return request()->_validation ?? [];
    }

    protected function validationData()
    {
        $data = [];

        foreach ($this->route()->parameters() as $parameter) {
            if ($parameter instanceof Resource) {
                $data = array_merge($data, $parameter->toArray());
            }
        }

        return $this->cleanData($data) ?? [];
    }

    protected function cleanData(array $data)
    {
        return array_filter($data, function ($value) {
            if (is_array($value)) {
                $value = $this->cleanData($value);
            }

            return ! is_null($value);
        }) ?: null;
    }

    protected function failedValidation(Validator $validator)
    {
        throw (new ValidationException($validator))
            ->errorBag($this->errorBag);
    }
}
