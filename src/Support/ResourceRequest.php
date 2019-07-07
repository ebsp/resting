<?php

namespace Seier\Resting\Support;

use Seier\Resting\Query;
use Seier\Resting\Params;
use Seier\Resting\Resource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class ResourceRequest extends FormRequest
{
    protected $redirect = null;

    protected $data = [
        'query' => [],
        'param' => [],
        'body' => [],
    ];

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return $this->formatData($this->getRequest()->_validation);
    }

    protected function validationData()
    {
        foreach ($this->route()->parameters() as $parameter) {
            $group = null;
            $data = $parameter->toArray();

            if ($parameter instanceof Query) {
                $group = 'query';
            } elseif ($parameter instanceof Params) {
                $group = 'param';
            } elseif ($parameter instanceof Resource) {
                $group = 'body';

                if ($this->getRequest()->_arrayBody) {
                    $data = [$data];
                }
            }

            $this->mergeData($data, $group);
        }

        return $this->cleanData($this->data);
    }

    protected function mergeData(array $data, string $group)
    {
        $this->data[$group] = array_merge($this->data[$group] ?? [], $data);
    }

    protected function cleanData(array $data)
    {
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                $cleanedArray = $this->cleanData($value);
                $value = ! count($cleanedArray) ? null : $cleanedArray;
            }
        }

        return array_filter($data, function ($value) {
            return ! is_null($value);
        });
    }

    protected function formatData($array, $prepend = '', $depth = 0, $level = 0)
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && ! empty($value) && $depth >= $level) {
                $results = array_merge($results, $this->formatData($value, $prepend.$key.'.', $depth, $level+1));
            } else {
                $results[$prepend.$key] = $value;
            }
        }

        return $results;
    }

    protected function failedValidation(Validator $validator)
    {
        $exception = $this->getValidationException();

        throw (new $exception($validator))->errorBag($this->errorBag);
    }

    protected function getValidationException()
    {
        return config('resting.validation_exception');
    }

    protected function getRequest()
    {
        return app('request');
    }
}
