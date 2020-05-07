<?php

namespace Seier\Resting\Fields;

use Exception;
use Seier\Resting\Resource;
use Illuminate\Support\Collection;
use Seier\Resting\Support\OpenAPI;
use Seier\Resting\Rules\ResourceRule;
use Seier\Resting\Support\Resourcable;
use Seier\Resting\UnionResource;

class ResourceField extends FieldAbstract
{
    public $resource;

    public function __construct(Resource $resource)
    {
        $this->resource = $this->value = $resource;
    }

    public function getMutator($value)
    {
        if ($value instanceof UnionResource) {
            $value = $value->get();
        }

        return optional($value)->flatten();
    }

    public function setMutator($value)
    {
        if (is_null($value) && $this->isNullable()) {
            return $value;
        }

        // not sure why this one has to be here in order to work
        if ($value instanceof Resource) {
            return $value;
        }

        if ($value instanceof Collection) {
            return $this->value->setPropertiesFromCollection($value);
        }

        if (is_array($value)) {
            return $this->value->setPropertiesFromCollection(
                collect($value)
            );
        }

        if (method_exists($this->value, 'fromInput')) {
            return $this->value->fromInput($value);
        }

        if ($value instanceof Resourcable) {
            return $value->asResource();
        }

        if ($value instanceof Resource) {
            return $value;
        }

        if (is_null($value)) {
            $this->setNull();

            return $this->value->setNull();
        }

        $this->error(
            new Exception('Value cannot be applied to resource')
        );

        return $this->value;
    }

    public function __get($name)
    {
        return $this->value->{$name};
    }

    public function __set($name, $value)
    {
        return $this->value->{$name}->set($value);
    }

    protected function fieldValidation(): array
    {
        return $this->isNull() && $this->nullable ? [] : [
            new ResourceRule($this->resource, false)
        ];
    }

    public function defaultBuildValue()
    {
        return $this->value;
    }

    public function requiredFields(...$fields)
    {
        $this->required();

        foreach ($this->value->fields() as $name => $field) {
            /** @var Field $field */
            $field->required(
                in_array($name, $fields)
            );
        }

        return $this;
    }

    public function formatted()
    {
        return $this->value;
    }

    public function suppressErrors($should = false)
    {
        parent::suppressErrors($should);

        $this->value->suppressErrors($should);

        return $this;
    }

    public function type(): array
    {
        if ($this->value instanceof UnionResource) {
            return [
                'type' => 'object',
                'oneOf' => array_map(function ($resource) {
                    return ['$ref' => OpenAPI::componentPath(OpenAPI::resourceRefName($resource))];
                }, $this->value->getDependantResources()),
            ];
        }

        return [
            '$ref' => OpenAPI::componentPath(
                OpenAPI::resourceRefName(get_class($this->value))
            ),
        ];
    }

    public function nestedRefs(): array
    {
        return [
            'schema' => get_class($this->value),
        ];
    }
}
