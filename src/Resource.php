<?php

namespace Seier\Resting;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Seier\Resting\Fields\EnumField;
use Seier\Resting\Support\Response;
use Seier\Resting\Fields\ResourceField;
use Seier\Resting\Fields\FieldAbstract;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;
use Seier\Resting\Support\SuppressErrorsTrait;

abstract class Resource implements
    Arrayable,
    Jsonable,
    Responsable
{
    protected $_responseCode = 200;
    protected $_trimNullValues = true;
    protected $_original;
    protected $_always_expect_required = false;
    protected $_is_null = false;
    protected $_filled = false;
    protected $_raw = null;
    protected $_is_flattened = false;

    protected $request;

    use SuppressErrorsTrait;

    public static function create()
    {
        return new static;
    }

    /**
     * @param array $values
     * @param bool $suppressErrors
     * @return static
     */
    public static function fromArray(array $values, bool $suppressErrors = false) : self
    {
        return static::fromCollection(collect($values), $suppressErrors);
    }

    /**
     * @param Collection $values
     * @param bool $suppressErrors
     * @return static
     */
    public static function fromCollection(Collection $values, bool $suppressErrors = false) : self
    {
        return (new static)->suppressErrors($suppressErrors)->setPropertiesFromCollection($values);
    }

    public static function fromRequest(Request $request, bool $suppressErrors = false)
    {
        return static::fromArray($request->all(), $suppressErrors)->setRequest($request);
    }

    public static function fromRaw(array $data)
    {
        return (new static)->setRaw($data);
    }

    protected function setRaw(array $data)
    {
        $this->_raw = $data;

        return $this;
    }

    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    public function setPropertiesFromCollection(Collection $collection)
    {
        foreach ($this->fields() as $field => $value) {
            $this->touch();

            $property = $this->{$field};

            if ($property instanceof FieldAbstract && $collection->has($field)) {
                $property->suppressErrors($this->suppressErrors)->set(
                    $collection->get($field)
                );
            };
        }

        return $this;
    }

    public function only(...$values)
    {
        $this->fields()->diffKeys(array_combine($values, $values))->each(function ($_, $property) {
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
        if (is_array($this->_raw)) {
            return $this->_raw;
        }

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

    public function __get($name)
    {
        return optional();
    }

    public function toArray()
    {
        return $this->values();
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
        $this->_is_flattened = true;

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

    public function filled()
    {
        return $this->_is_null || $this->_filled;
    }

    public function touch()
    {
        $this->_filled = true;

        return $this;
    }

    public function isFlattened()
    {
        return $this->_is_flattened;
    }

    public function isNull()
    {
        return $this->_is_null;
    }

    public function setNull()
    {
        $this->_is_null = true;

        return $this;
    }
}
