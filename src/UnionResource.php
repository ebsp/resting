<?php


namespace Seier\Resting;


use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Seier\Resting\Support\OpenAPI;

class UnionResource extends Resource
{

    protected string $_unionDiscriminatorKey;
    protected array $_unionTypes;
    protected string $_currentDiscriminatorKey;

    public function __construct(string $discriminatorKey, array $types)
    {
        $this->_unionDiscriminatorKey = $discriminatorKey;
        $this->_unionTypes = $types;
    }

    public function get()
    {
        if (!$this->_currentDiscriminatorKey) {
            return $this;
        }

        return $this->_unionTypes[$this->_currentDiscriminatorKey];
    }

    public function setRaw(array $data)
    {
        return new static(
            $this->_unionDiscriminatorKey,
            $this->_unionTypes
        );
    }

    public function setPropertiesFromCollection(Collection $collection)
    {
        $this->_currentDiscriminatorKey = $collection->get($this->_unionDiscriminatorKey);
        $subResource = $this->_unionTypes[$collection->get($this->_unionDiscriminatorKey)];

        return $subResource->setPropertiesFromCollection($collection);
    }

    public function validation(Request $request, $overwriteRequirements = true)
    {
        $rules = parent::validation($request, $overwriteRequirements);
        $rules[$this->_unionDiscriminatorKey] = [
            'in:' . implode(',', array_keys($this->_unionTypes)),
            'required',
        ];

        return $rules;
    }

    public function copy()
    {
        return new static(
            $this->_unionDiscriminatorKey,
            $this->_unionTypes
        );
    }

    public function type(): array
    {
        $type = 'object';
        foreach ($this->_unionTypes as $unionType) {
            $oneOf[] = ['$ref' => OpenAPI::componentPath(OpenAPI::resourceRefName(get_class($unionType)))];
        }

        return compact('type', 'oneOf');
    }


    public function getDependantResources()
    {
        return array_map(fn(Resource $resource) => get_class($resource), $this->_unionTypes);
    }

    public function nestedRefs(): array
    {
        return [
            'schema' => implode('|', array_map(fn($item) => get_class($item), $this->_unionTypes)),
        ];
    }
}