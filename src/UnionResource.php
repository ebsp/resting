<?php


namespace Seier\Resting;


use Closure;
use ReflectionClass;
use Illuminate\Support\Collection;
use Seier\Resting\Support\OpenAPI;

abstract class UnionResource extends Resource
{

    private Closure $_unionResourcesFactory;
    private ?array $_unionResources = null;
    private string $_unionDiscriminatorKey;
    private mixed $_discriminatorValue = null;

    public function __construct(string $unionDiscriminator, Closure $unionResourcesFactory)
    {
        $this->_unionDiscriminatorKey = $unionDiscriminator;
        $this->_unionResourcesFactory = $unionResourcesFactory;
        $degree = $this->getUnionDepth($this);
        if ($degree === 0) {
            $this->_unionResources = $unionResourcesFactory();
        }
    }

    public function get()
    {
        if ($this->_discriminatorValue !== null && array_key_exists($this->_discriminatorValue, $this->_unionResources)) {
            return $this->_unionResources[$this->_discriminatorValue];
        }

        return $this;
    }

    public function setRaw(array $data): static
    {
        return new static($this->_unionDiscriminatorKey, $this->_unionResources);
    }

    public function setFieldsFromCollection(Collection $collection): static
    {
        $unionDegree = $this->getUnionDepth($this);

        if ($unionDegree === 0) {

            if (!$collection->has($this->_unionDiscriminatorKey)) {
                return parent::setFieldsFromCollection($collection);
            }

            $this->_discriminatorValue = $collection->get($this->_unionDiscriminatorKey);
            if (!array_key_exists($this->_discriminatorValue, $this->_unionResources)) {
                return parent::setFieldsFromCollection($collection);
            }

            $subResource = $this->_unionResources[$this->_discriminatorValue];
            $subResource->setFieldsFromCollection($collection);
            return $this;

        } else {
            return parent::setFieldsFromCollection($collection);
        }
    }

    public function toArray(): array
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function toJson($options = 0): bool|string
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function toResponseArray(): array
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    private function delegate(string $method, array $arguments)
    {
        $unionDegree = $this->getUnionDepth($this);

        // we cannot delegate to a sub-resource when _currentDiscriminatorValue is not set,
        // since we cannot know which of the sub-resources to use
        if ($unionDegree === 0 && $this->_discriminatorValue !== null && array_key_exists($this->_discriminatorValue, $this->_unionResources)) {
            return $this->get()->{$method}(...$arguments);
        } else {
            return parent::{$method}(...$arguments);
        }
    }

    public function type(): array
    {
        $type = 'object';
        foreach ($this->_unionResources as $unionInstance) {
            $oneOf[] = ['$ref' => OpenAPI::componentPath(OpenAPI::resourceRefName(get_class($unionInstance)))];
        }

        return compact('type', 'oneOf');
    }

    public function getDependantResources(): array
    {
        if (!$this->_unionResources) {
            $this->_unionResources = $this->getUnionDepth($this) === 0
                ? ($this->_unionResourcesFactory)()
                : [];
        }

        return array_values(array_map(fn(Resource $resource) => $resource::class, $this->_unionResources));
    }

    public function getResourceMap(): ?array
    {
        if (!$this->_unionResources) {
            $this->_unionResources = ($this->_unionResourcesFactory)();
        }

        return $this->_unionResources;
    }

    private function getUnionDepth(UnionResource $resource): int
    {
        $depth = 0;
        $currentResource = $resource::class;

        while (true) {

            $reflection = new ReflectionClass($currentResource);
            $parent = $reflection->getParentClass();
            if ($parent === false) {
                return 0;
            }

            if ($parent->getName() === UnionResource::class) {
                return $depth;
            } else {
                $currentResource = $parent->getName();
                $depth++;
            }
        }
    }

    public function getDiscriminatorKey(): string
    {
        return $this->_unionDiscriminatorKey;
    }
}