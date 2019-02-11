<?php

namespace Seier\Resting;

use Illuminate\Support\Collection;
use Seier\Resting\Fields\FieldAbstract;

class DynamicResource extends Resource
{
    protected $fields = [];

    public function __construct()
    {
        $this->fields = collect();
    }

    public function fields() : Collection
    {
        return $this->fields;
    }

    public function addField($key, FieldAbstract $field)
    {
        $this->fields->put($key, $field);

        return $this;
    }

    public function removeField($key)
    {
        $this->fields->forget($key);

        return $this;
    }

    public function __get($key)
    {
        return $this->fields->get($key);
    }
}
