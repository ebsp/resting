<?php

namespace Seier\Resting\Fields;

use Exception;
use Seier\Resting\Support\SilenceErrorsTrait;

abstract class FieldAbstract
{
    protected $value;
    protected $required = false;
    protected $hidden = false;
    protected $nullable = true;
    protected $filled = false;
    protected $additionalRules = [];
    protected $isNull = false;

    use SilenceErrorsTrait;

    final public function get()
    {
        return is_null($this->value) ? null : $this->getMutator($this->value);
    }

    public function formatted()
    {
        return $this->get();
    }

    public function nullable(bool $is)
    {
        $this->nullable = $is;

        return $this;
    }

    public function defaultBuildValue()
    {
        return null;
    }

    final public function set($value, $condition = true)
    {
        if ($condition) {
            $this->value = /*is_null($value) && $this->nullable
                ? $this->defaultBuildValue()
                :*/ $this->setMutator($value);
        }

        $this->filled = true;

        return $this;
    }

    final public function unset()
    {
        $this->value = null;

        return $this;
    }

    public function __set($name, $value)
    {
        return $this->set($value);
    }

    protected function getMutator($value)
    {
        if ($this->nullable && ! $this->filled) {
            return null;
        }

        return $value;
    }

    protected function setMutator($value)
    {
        return $value;
    }

    abstract protected function fieldValidation() : array;
    abstract public function type() : array;

    public function nestedRefs() : array
    {
        return [];
    }

    final public function validation() : array
    {
        $rules = $this->fieldValidation();

        $rules[] = $this->required ? 'required' : 'nullable';

        return array_merge($rules, $this->additionalRules);
    }

    public function addValidation(array $rules)
    {
        $this->additionalRules = array_merge($this->additionalRules, $rules);

        return $this;
    }

    public function required($is = true)
    {
        $this->required = $is;

        return $this;
    }

    public function isRequired()
    {
        return $this->required;
    }

    public function isHidden()
    {
        return $this->hidden;
    }

    public function setHidden($hidden = true)
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function throwErrors($should = true)
    {
        $this->shouldThrowErrors = $should;

        return $this;
    }

    protected function error(Exception $exception)
    {
        if ($this->shouldThrowErrors) {
            throw $exception;
        }
    }

    public function filled()
    {
        return (bool) $this->filled;
    }

    public function isNull()
    {
        return $this->isNull;
    }

    public function setNull()
    {
        $this->isNull = true;

        return $this;
    }

    public function touch()
    {
        $this->filled = true;

        return $this;
    }
}
