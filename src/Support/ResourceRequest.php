<?php

namespace Seier\Resting\Support;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ResourceRequest extends FormRequest
{
    protected $redirect = null;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $request = request();

        return array_merge(
            optional($this->route()->_query)->validation($request) ?? [],
            optional($this->route()->_resource)->validation($request) ?? []
        );
    }

    protected function validationData()
    {
        $resourceData = optional($this->route()->_resource)->toArray() ?? [];
        $queryData = optional($this->route()->_query)->toArray() ?? [];

        return array_merge($resourceData, $queryData);
    }

    protected function failedValidation(Validator $validator)
    {
        throw (new ValidationException($validator))
            ->errorBag($this->errorBag);
    }
}
