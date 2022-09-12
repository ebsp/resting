<?php

namespace Seier\Resting\Fields;

use Closure;
use Exception;
use ReflectionClass;
use Seier\Resting\UnionResource;
use Seier\Resting\Support\OpenAPI;
use Illuminate\Support\Collection;
use Seier\Resting\Support\Resourcable;
use Seier\Resting\Resource as RestingResource;
use Seier\Resting\Exceptions\ValidationException;
use Seier\Resting\Validation\Errors\NotSubclassOfError;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

class ResourceField extends Field
{
    private Closure $resourceFactory;
    private RestingResource $resource;
    private ReflectionClass $resourceReflection;

    public function __construct(Closure $resourceFactory)
    {
        parent::__construct();

        $this->setResourcePrototypeFactory($resourceFactory);
    }

    public function setResourcePrototypeFactory(Closure $resourceFactory): static
    {
        $this->resourceFactory = $resourceFactory;
        $this->resource = $resourceFactory();
        $this->resourceReflection = new ReflectionClass($this->resource::class);

        return $this;
    }

    public function get(): ?RestingResource
    {
        if ($this->resource instanceof UnionResource) {
            return $this->value?->get();
        }

        return $this->value;
    }

    public function set($value): static
    {
        if ($value === null) {
            return parent::set($value);
        }

        if ($value instanceof Resourcable) {
            $value = $value->asResource();
        }

        if ($value instanceof RestingResource) {

            if (!$this->resourceReflection->isInstance($value)) {
                throw new ValidationException([new NotSubclassOfError($this->resourceReflection, $value)]);
            }

            $this->value = $value;

            return $this;
        }

        if ($value instanceof Collection) {
            $value = $value->toArray();
        }

        if (is_array($value)) {
            $this->value = ($this->value ?? ($this->resourceFactory)())->setFieldsFromCollection(collect($value));
            $this->isFilled = true;
            return $this;
        }

        $this->resource = $value;
        $this->isFilled = true;

        return $this;
    }

    public function resourceAsDefault(): static
    {
        $this->nullDefault($this->resourceFactory);

        return $this;
    }

    public function formatted(): mixed
    {
        return $this->value?->toArray();
    }

    public function type(): array
    {
        $resource = ($this->resourceFactory)();

        if ($resource instanceof UnionResource) {
            return [
                'type' => 'object',
                'oneOf' => array_map(function ($resource) {
                    return ['$ref' => OpenAPI::componentPath(OpenAPI::resourceRefName($resource))];
                }, $resource->getDependantResources()),
            ];
        }

        return [
            '$ref' => OpenAPI::componentPath(
                OpenAPI::resourceRefName(get_class($resource))
            ),
        ];
    }

    public function getResourcePrototype(): RestingResource
    {
        return $this->resource;
    }

    public function nestedRefs(): array
    {
        return [
            'schema' => get_class($this->resource),
        ];
    }

    public function getReferenceResource()
    {
        return $this->resource;
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        throw new Exception('unsupported');
    }
}
