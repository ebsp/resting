<?php


namespace Seier\Resting;


use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Seier\Resting\Support\OpenAPI;

abstract class UnionResource extends Resource
{

    private string $_unionDiscriminatorKey;
    private $_unionResourcesFactory;
    private ?array $_unionResources;
    private $_currentDiscriminatorValue;

    public function __construct(string $unionDiscriminator, callable $unionResourcesFactory)
    {
        $this->_unionDiscriminatorKey = $unionDiscriminator;
        $this->_unionResourcesFactory = $unionResourcesFactory;
        $degree = $this->getUnionDegree($this);
        if ($degree === 0) {
            $this->_unionResources = $unionResourcesFactory();
        } else {
            $this->_unionResources = null;
        }
    }

    public function get()
    {
        if ($this->_currentDiscriminatorValue) {
            return $this->_unionResources[$this->_currentDiscriminatorValue];
        }

        return $this;
    }

    public function setRaw(array $data)
    {
        return new static($this->_unionDiscriminatorKey, $this->_unionResources);
    }

    public function setPropertiesFromCollection(Collection $collection)
    {
        $unionDegree = $this->getUnionDegree($this);

        if ($unionDegree === 0) {
            $this->_currentDiscriminatorValue = $collection->get($this->_unionDiscriminatorKey);
            $subResource = $this->_unionResources[$this->_currentDiscriminatorValue];
            return $subResource->setPropertiesFromCollection($collection);
        } else {
            return parent::setPropertiesFromCollection($collection);
        }
    }

    public function toArray()
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function flatten()
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function values()
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function toResponse($request)
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function original()
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function toJson($options = 0)
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function responseCode($code): Resource
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    public function toResponseArray()
    {
        return $this->delegate(__FUNCTION__, func_get_args());
    }

    private function delegate(string $method, array $arguments)
    {
        $unionDegree = $this->getUnionDegree($this);

        // we cannot delegate to a sub-resource when _currentDiscriminatorValue is not set,
        // since we cannot know which of the sub-resources to use
        if ($unionDegree === 0 && $this->_currentDiscriminatorValue) {
            return $this->get()->{$method}(...$arguments);
        } else {
            return parent::{$method}(...$arguments);
        }
    }

    public function validation(Request $request, $overwriteRequirements = true)
    {
        if ($this->_unionResources === null) {
            $this->_unionResources = ($this->_unionResourcesFactory)();
        }

        $unionDegree = $this->getUnionDegree($this);
        $discriminatorRules = [$this->_unionDiscriminatorKey => [
            'in:' . implode(',', array_keys($this->_unionResources)),
            'required',
        ]];

        // we cannot delegate to a sub-resource when _currentDiscriminatorValue is not set,
        // since we cannot know which of the sub-resources to use
        if ($unionDegree === 0) {

            $getRequestResource = function () use ($request) {
                $values = $request->all();
                $exists = array_key_exists($this->_unionDiscriminatorKey, $values);
                if ($exists) {
                    $this->_currentDiscriminatorValue = $values[$this->_unionDiscriminatorKey];
                }

                return !$this->_currentDiscriminatorValue ? null : $this->_unionResources[$this->_currentDiscriminatorValue];
            };

            $subResource = $this->_currentDiscriminatorValue = $this->_currentDiscriminatorValue
                ? $this->_unionResources[$this->_currentDiscriminatorValue]
                : $getRequestResource();

            if ($subResource !== null) {
                return array_merge_recursive($discriminatorRules, $subResource->{__FUNCTION__}(...func_get_args()));
            }
        }

        return array_merge_recursive($discriminatorRules, parent::{__FUNCTION__}(...func_get_args()));
    }

    public function type(): array
    {
        $type = 'object';
        foreach ($this->_unionResources as $unionInstance) {
            $oneOf[] = ['$ref' => OpenAPI::componentPath(OpenAPI::resourceRefName(get_class($unionInstance)))];
        }

        return compact('type', 'oneOf');
    }


    public function getDependantResources()
    {
        return array_map(fn(Resource $resource) => get_class($resource), $this->_unionResources);
    }

    private function getUnionDegree($item)
    {
        $level = 0;
        $currentResource = get_class($item);
        while (true) {
            $reflection = new \ReflectionClass($currentResource);
            $parent = $reflection->getParentClass();
            if ($parent === false) {
                return 0;
            }

            if ($parent->getName() === UnionResource::class) {
                return $level;
            } else {
                $currentResource = $parent->getName();
                $level++;
            }
        }

        return 0;
    }
}