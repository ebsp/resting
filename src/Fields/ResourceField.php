<?php

namespace Seier\Resting\Fields;

use Exception;
use Seier\Resting\Resource;
use Illuminate\Support\Collection;
use Seier\Resting\Rules\ResourceRule;
use Seier\Resting\Support\Resourcable;

class ResourceField extends FieldAbstract
{
    public function __construct(Resource $resource)
    {
        $this->value = $resource;
    }

    public function getMutator($value)
    {
        return optional($value)->flatten();
    }

    public function setMutator($value)
    {
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
            return $this->value;
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

    protected function fieldValidation() : array
    {
        return [
            new ResourceRule($this->value)
        ];
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

    public function type() : array
    {
        return [
            '$ref' => get_class($this->value),
        ];
    }

    public function nestedRefs() : array
    {
        return [
            'schema' => get_class($this->value),
        ];
    }
}
