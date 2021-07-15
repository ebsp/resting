<?php


namespace Seier\Resting;


use Closure;
use ReflectionClass;
use Illuminate\Support\Collection;
use Seier\Resting\Support\OpenAPI;
use Seier\Resting\Exceptions\ValidationException;
use Seier\Resting\Validation\Errors\UnknownUnionDiscriminatorValidationError;

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
        $degree = self::getUnionDepth($this);
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
        $unionDegree = self::getUnionDepth($this);

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

    public function toArray(array $filter = null, array $rename = null, bool $requireFilled = false): array
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function toJson($options = 0): bool|string
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function toResponseArray(array $filter = null, array $rename = null, bool $requireFilled = false): array
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    private function delegate(string $method, array $arguments)
    {
        $unionDegree = self::getUnionDepth($this);

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
            $this->_unionResources = self::getUnionDepth($this) === 0
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

    private static function getUnionDepth(UnionResource $resource): int
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

    public static function fromArray(array $values): static
    {
        return self::fromCollection(
            collect($values)
        );
    }

    public static function fromCollection(Collection $values): static
    {
        $staticInstance = new static();
        $unionDegree = self::getUnionDepth($staticInstance);

        if ($unionDegree > 0) {
            return parent::fromCollection($values);
        }

        if (!$values->has($staticInstance->_unionDiscriminatorKey)) {
            return $staticInstance->setFieldsFromCollection($values);
        }

        $staticInstance->_discriminatorValue = $values->get($staticInstance->_unionDiscriminatorKey);
        if (!array_key_exists($discriminatorValue = $staticInstance->_discriminatorValue, $staticInstance->_unionResources)) {
            throw new ValidationException([
                (new UnknownUnionDiscriminatorValidationError(
                    array_keys($staticInstance->_unionResources),
                    $discriminatorValue
                ))->prependPath(
                    $staticInstance->_unionDiscriminatorKey
                ),
            ]);
        }

        $subResource = $staticInstance->_unionResources[$staticInstance->_discriminatorValue];
        $subResource->setFieldsFromCollection($values);

        return $subResource;
    }

    public function getDiscriminatorKey(): string
    {
        return $this->_unionDiscriminatorKey;
    }
}