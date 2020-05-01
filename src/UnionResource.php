<?php


namespace Seier\Resting;


use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class UnionResource extends Resource
{

    protected string $_unionDiscriminatorValue;
    protected array $_unionTypes;
    protected string $_currentDiscriminatorKey;

    public function __construct(string $discriminatorKey, array $types)
    {
        $this->_unionDiscriminatorValue = $discriminatorKey;
        $this->_unionTypes = $types;
    }

    public function get()
    {
        if (!$this->_currentDiscriminatorKey) {
            return $this;
        }

        return $this->_unionTypes[$this->_currentDiscriminatorKey];
    }

    public function setPropertiesFromCollection(Collection $collection)
    {
        $this->_currentDiscriminatorKey = $collection->get($this->_unionDiscriminatorValue);
        $subResource = $this->_unionTypes[$collection->get($this->_unionDiscriminatorValue)];

        return $subResource->setPropertiesFromCollection($collection);
    }

    public function validation(Request $request, $overwriteRequirements = true)
    {
        $rules = parent::validation($request, $overwriteRequirements);
        $rules[$this->_unionDiscriminatorValue] = [
            'in:' . implode(',', array_keys($this->_unionTypes)),
            'required',
        ];

        return $rules;
    }

    public function copy()
    {
        return new static(
            $this->_unionDiscriminatorValue,
            $this->_unionTypes
        );
    }
}