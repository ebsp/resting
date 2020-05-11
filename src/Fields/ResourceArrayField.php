<?php

namespace Seier\Resting\Fields;

use Illuminate\Support\Arr;
use Seier\Resting\Resource;
use Seier\Resting\Support\OpenAPI;
use Illuminate\Support\Collection;
use Seier\Resting\Rules\ResourceArrayRule;
use Seier\Resting\Exceptions\NotArrayException;
use Seier\Resting\UnionResource;

class ResourceArrayField extends FieldAbstract
{
    protected $resource;

    public function __construct(Resource $resource)
    {
        $this->resource = $resource;
    }

    public function push($value)
    {
        $this->set(array_merge($this->get() ?? [], [$value]));

        return $this;
    }

    public function getMutator($value)
    {
        return array_map(function ($_value) {
            return (new ResourceField(
                $this->resource->copy()
            ))->set($_value)->get();
        }, $value ?? []);
    }

    public function setMutator($value)
    {
        if (is_null($value) && $this->isNullable()) {
            return $value;
        }

        if (!Arr::accessible($value)) {
            throw new NotArrayException('Field value is not an array');
        }

        if ($value instanceof Collection) {
            $value = $value->all();
        }

        return array_map(function ($_value) {

            if ($this->resource instanceof UnionResource) {
                $instance = $this->resource->copy();
            } else {
                $class = get_class($this->resource);
                $instance = new $class;
            }

            return (new ResourceField(
                $instance
            ))->setMutator(
                $_value
            );
        }, array_filter($value ?? [], function ($_value) {
            return is_array($_value) || is_object($_value);
        }));
    }

    public function __set($name, $value)
    {
        return $this->value = $value;
    }

    protected function fieldValidation(): array
    {
        return [
            new ResourceArrayRule($this->resource)
        ];
    }

    public function requiredFields(...$fields)
    {
        foreach ($this->resource->fields() as $name => $field) {
            /** @var Field $field */
            $field->required(
                in_array($name, $fields)
            );
        }

        return $this;
    }

    public function formatted()
    {
        return array_map(function ($resource) {
            return $resource->toResponseArray();
        }, $this->value ?? []);
    }

    public function suppressErrors($should = false)
    {
        parent::suppressErrors($should);

        $this->resource->suppressErrors($should);

        return $this;
    }

    public function resources()
    {
        return $this->resource;
    }

    public function type(): array
    {
        if ($this->resource instanceof UnionResource) {
            return [
                'type' => 'array',
                'items' => ['oneOf' => array_map(function ($resource) {
                    return ['$ref' => OpenAPI::componentPath(OpenAPI::resourceRefName($resource))];
                }, $this->resource->getDependantResources())],
            ];
        }

        return [
            'type' => 'array',
            'items' => [
                '$ref' => OpenAPI::componentPath(
                    OpenAPI::resourceRefName(get_class($this->resource))
                ),
            ]
        ];
    }

    public function nestedRefs(): array
    {
        return [
            'schema' => get_class($this->resource),
        ];
    }
}
