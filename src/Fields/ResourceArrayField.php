<?php

namespace Seier\Resting\Fields;

use Closure;
use Countable;
use ArrayAccess;
use ArrayIterator;
use ReflectionClass;
use IteratorAggregate;
use Seier\Resting\UnionResource;
use Seier\Resting\Support\OpenAPI;
use Illuminate\Support\Collection;
use Seier\Resting\Validation\ArrayValidator;
use Seier\Resting\Resource as RestingResource;
use Seier\Resting\Exceptions\ValidationException;
use Seier\Resting\Exceptions\ValidationExceptionHandler;
use Seier\Resting\Validation\Errors\NotArrayValidationError;
use Seier\Resting\Validation\Secondary\Arrays\ArrayValidation;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

class ResourceArrayField extends Field implements ArrayAccess, Countable, IteratorAggregate
{

    use ArrayValidation;

    protected Closure $resourceFactory;
    protected RestingResource $resource;
    protected ReflectionClass $reflectionClass;
    protected ArrayValidator $validator;

    public function __construct(Closure $resourceFactory)
    {
        parent::__construct();

        $this->resourceFactory = $resourceFactory;
        $this->resource = $resourceFactory();
        $this->reflectionClass = new ReflectionClass($this->resource);
        $this->validator = new ArrayValidator();
    }

    public function getValidator(): ArrayValidator
    {
        return $this->validator;
    }

    public function get(): ?array
    {
        if ($this->value === null) {
            return null;
        }

        if ($this->resource instanceof UnionResource) {
            return array_map(function (UnionResource $resource) {
                return $resource->get();
            }, $this->value);
        }

        return $this->value;
    }

    public function count(): int
    {
        return $this->isNull() ? 0 : count($this->value);
    }

    public function push(RestingResource $value): self
    {
        if ($this->value === null) {
            $this->value = [];
        }

        array_push($this->value, $value);

        return $this;
    }

    private function isAssociativeArray(array $array): bool
    {
        if (empty($array)) {
            return false;
        }

        foreach ($array as $key => $value) {
            if (is_string($key)) {
                return true;
            }
        }

        return false;
    }

    public function clear()
    {
        if ($this->value) {
            $this->value = [];
        }
    }

    public function set($value): static
    {
        if ($value === null) {
            return parent::set($value);
        }

        if ($value instanceof Collection) {
            $value = $value->values()->toArray();
        }

        if (!is_array($value) || $this->isAssociativeArray($value)) {
            throw new ValidationException([new NotArrayValidationError($value)]);
        }

        $resources = [];
        $errors = [];

        foreach ($value as $index => $element) {

            if (is_object($element) && $this->reflectionClass->isInstance($element)) {
                $resources[] = $element;
                continue;
            }

            $resources[] = $resource = ($this->resourceFactory)();
            if (!is_array($element)) {
                $errors[] = new NotArrayValidationError($element);
                continue;
            }

            $exceptionHandler = new ValidationExceptionHandler();
            $exceptionHandler->suppress($index, fn() => $resource->set($element));
            $exceptionHandler->moveErrors($errors);
        }

        $errors = array_merge($errors, $this->validator->validate($resources));

        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }

        $this->value = $resources;
        $this->isFilled = true;

        return $this;
    }

    public function resource(): RestingResource
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

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->value);
    }

    public function offsetGet($offset)
    {
        return $this->value[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->value[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->value[$offset]);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->get() ?? []);
    }

    public function getResourceFactory(): Closure
    {
        return $this->resourceFactory;
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        return $this->validator;
    }
}
