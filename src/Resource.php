<?php

namespace Seier\Resting;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Seier\Resting\Support\Response;
use Seier\Resting\Fields\ResourceField;
use Seier\Resting\Fields\FieldAbstract;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;
use Seier\Resting\Support\SilenceErrorsTrait;

abstract class Resource implements
    Arrayable,
    Jsonable,
    Responsable
{
    protected $_responseCode = 200;
    protected $_trimNullValues = true;
    protected $_original;
    protected $_always_expect_required = false;
    protected $request;

    use SilenceErrorsTrait;

    public static function create()
    {
        return new static;
    }

    /**
     * @param array $values
     * @param bool $shouldThrowErrors
     * @return static
     */
    public static function fromArray(array $values, bool $shouldThrowErrors = true) : self
    {
        return static::fromCollection(collect($values), $shouldThrowErrors);
    }

    /**
     * @param Collection $values
     * @param bool $shouldThrowErrors
     * @return static
     */
    public static function fromCollection(Collection $values, bool $shouldThrowErrors = true) : self
    {
        return (new static)->throwErrors($shouldThrowErrors)->setPropertiesFromCollection($values);
    }

    public static function fromRequest(Request $request)
    {
        return static::fromArray($request->all(), false)->setRequest($request);
    }

    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    public function setPropertiesFromCollection(Collection $collection)
    {
        foreach ($this->fields() as $field => $value) {
            $property = $this->{$field};

            if ($property instanceof FieldAbstract && $collection->has($field)) {
                $property->throwErrors($this->shouldThrowErrors);

                $property->set(
                    $collection->get($field)
                );
            };
        }

        return $this;
    }

    public function only(...$values)
    {
        $this->fields()->intersectByKeys($values)->each(function ($_, $property) {
            unset($this->{$property});
        });

        return $this;
    }

    public function fields() : Collection
    {
        return collect(
            objectProperties($this)
        );
    }

    public function values()
    {
        return $this->fields()
            ->filter(function ($field) {
                return ! ($field instanceof FieldAbstract && $field->isHidden());
            })
            ->map(function ($field) {
                if ($field instanceof ResourceField && ! $field->filled()) {
                    return null;
                }

                if ($field instanceof FieldAbstract) {
                    return $field->formatted();
                }

                if (is_array($field)) {
                    return array_map(function ($element) {
                        if ($element instanceof Resource) {
                            return $element->toArray();
                        }

                        return $element;
                    }, $field);
                }

                return $field;
            })->toArray();
    }

    public function toArray()
    {
        return $this->values();
    }

    public function toObject()
    {
        return json_decode($this->toJson());
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    public function copy()
    {
        return clone $this;
    }

    /**
     * @return static
     */
    public function flatten()
    {
        $copy = $this->copy();

        foreach ($copy->fields() as $field => $value) {
            if ($value instanceof FieldAbstract) {
                $copy->{$field} = $value->get();
            }
        }

        $copy->setOriginal($this);
        
        return $copy;
    }

    public function original()
    {
        return $this->_original;
    }

    public function setOriginal($original)
    {
        $this->_original = $original;

        return $this;
    }

    public function validation(Request $request, $overwriteRequirements = true)
    {
        return $this->fields()->filter(function ($field) {
            return $field instanceof FieldAbstract;
        })->map(function (FieldAbstract $field) use ($request, $overwriteRequirements) {
            return $field->required(
                $overwriteRequirements
                    ? $field->isRequired() && $this->requiredFieldsExpected($request)
                    : $field->isRequired()
            )->validation();
        })->toArray();
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function toResponse($request)
    {
        return new JsonResponse(
            $this->responseData(), $this->_responseCode ?? 200
        );
    }

    protected function responseData() : array
    {
        return (new Response(
            $this->toResponseArray()
        ))->toArray();
    }

    public function toResponseArray()
    {
        return $this->_trimNullValues
            ? nn($this->toArray())
            : $this->toArray();
    }

    public function requiredFieldsExpected(Request $request)
    {
        return in_array($request->method(), ['POST', 'PUT']) || $this->_always_expect_required;
    }

    public function alwaysExpectRequired($should = true)
    {
        $this->_always_expect_required = $should;

        return $this;
    }

    public function responseCode($code) : self
    {
        $this->_responseCode = $code;

        return $this;
    }

    public function trimNullValues(bool $should = null)
    {
        if (! is_null($should)) {
            return $this->_trimNullValues = $should;
        }

        return $this->_trimNullValues;
    }

    public function fromEloquent($model)
    {
        return $this;
    }

    public function prepare()
    {
        //
    }
}
