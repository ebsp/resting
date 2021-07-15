<?php

namespace Seier\Resting;

use Seier\Resting\Fields\Field;
use Illuminate\Support\Collection;
use Seier\Resting\Exceptions\DynamicResourceFieldException;

class DynamicResource extends Resource
{

    protected Collection $fields;

    public function __construct()
    {
        $this->fields = collect();
    }

    public function fields(array $filter = null, array $rename = null, bool $requireFilled = false): Collection
    {
        return $this->transformFields(
            $this->fields->collect(),
        );
    }

    public function withField(string $property, Field $field): static
    {
        $this->fields->put($property, $field);

        return $this;
    }

    public function __get($key): Field
    {
        if (!$this->fields->has($key)) {
            throw new DynamicResourceFieldException();
        }

        return $this->fields->get($key);
    }
}
