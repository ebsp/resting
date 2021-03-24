<?php

namespace Seier\Resting;

use Seier\Resting\Fields\Field;
use Illuminate\Support\Collection;
use Seier\Resting\Fields\ResourceField;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Seier\Resting\Fields\ResourceArrayField;
use Seier\Resting\Validation\Secondary\Panics;
use Seier\Resting\Marshaller\ResourceMarshaller;
use Seier\Resting\Exceptions\ValidationException;
use Seier\Resting\Validation\Predicates\ResourceContext;
use Seier\Resting\Validation\Predicates\ArrayResourceContext;

abstract class Resource implements Arrayable, Jsonable
{

    use Panics;

    private bool $removeNulls = true;
    private bool $removeEmptyArrays = true;
    private mixed $raw = null;

    public static function create(): static
    {
        return new static;
    }

    public static function fromArray(array $values): static
    {
        return static::fromCollection(collect($values));
    }

    public static function fromCollection(Collection $values): static
    {
        $resource = new static();
        $context = new ArrayResourceContext(
            $resource->fields()->toArray(),
            $values->toArray(),
            isStringBased: false
        );

        $resource->prepare($context);
        $resource->setFieldsFromCollection($values);
        $resource->finish();

        return $resource;
    }

    public static function fromRaw(array $data): static
    {
        return (new static)->setRaw($data);
    }

    public function mapMany(iterable $values, callable $mapper): array
    {
        $mapped = [];
        foreach ($values as $value) {
            $mapped[] = $mapper($this, $value)->toResponseArray();
        }

        return $mapped;
    }

    public function setRaw(array $data): static
    {
        $this->raw = $data;

        return $this;
    }

    public function set(array|Collection $values): static
    {
        $this->setFieldsFromCollection(collect($values));

        return $this;
    }

    public function setFieldsFromCollection(Collection $collection): static
    {
        $marshaller = new ResourceMarshaller();
        $marshaller->marshalResourceFields($this, $collection->toArray());
        if ($errors = $marshaller->getValidationErrors()) {
            throw new ValidationException($errors);
        }

        return $this;
    }

    public function only(Field ...$fields): static
    {
        $hashCodes = [];
        foreach ($fields as $field) {
            $hashCodes[spl_object_hash($field)] = $field;
        }

        $this->fields()->each(function ($field) use ($hashCodes) {
            $field->enable(array_key_exists(spl_object_hash($field), $hashCodes));
        });

        return $this;
    }

    public function fields(): Collection
    {
        return collect(get_object_vars($this))
            ->filter(fn($value) => $value instanceof Field && $value->isEnabled());
    }

    protected function values(bool $format)
    {
        if (is_array($this->raw)) {
            return $this->raw;
        }

        return $this->fields()
            ->map(function ($field) use ($format) {

                if ($field instanceof ResourceField) {
                    $resource = $field->get();
                    return $format ? $resource?->toResponseArray() : $resource?->toArray();
                }

                if ($field instanceof ResourceArrayField) {

                    $value = $field->get();
                    if ($value !== null) {
                        return array_map(function (Resource $resource) use ($format) {
                            return $format ? $resource->toResponseArray() : $resource->toArray();
                        }, $field->get());
                    }

                    return null;
                }

                if ($field instanceof Field) {
                    return $format ? $field->formatted() : $field->get();
                }

                $this->panic();

            })->toArray();
    }

    public function toArray(): array
    {
        return $this->values(format: false);
    }

    public function toJson($options = 0): bool|string
    {
        return json_encode($this->toResponseArray(), $options);
    }

    public function copy(): static
    {
        return new static();
    }

    public function toResponseArray()
    {
        $array = $this->values(format: true);

        if (!$this->removeNulls && !$this->removeEmptyArrays) {
            return $array;
        }

        return array_filter($array, function (mixed $value) {
            return (
                (!$this->removeNulls || $value !== null) &&
                (!$this->removeEmptyArrays || $value !== [])
            );
        });
    }

    public function removeNulls(bool $should): static
    {
        $this->removeNulls = $should;

        return $this;
    }

    public function removeEmptyArrays(bool $should): static
    {
        $this->removeEmptyArrays = $should;

        return $this;
    }

    /**
     * Called before validation and hydration is performed on any fields on the resource.
     *
     * @param ResourceContext $context
     */
    public function prepare(ResourceContext $context)
    {

    }

    /**
     * Called after validation and hydration has finished on the fields on the resource.
     */
    public function finish()
    {

    }

    public function getDependantResources(): array
    {
        return [];
    }
}
