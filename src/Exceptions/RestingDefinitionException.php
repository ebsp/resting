<?php

namespace Seier\Resting\Exceptions;

use ReflectionClass;
use ReflectionParameter;
use Illuminate\Routing\Route;

class RestingDefinitionException extends RestingException
{
    public static function cannotResolveParameter(Route $route, ReflectionParameter $parameter): static
    {
        return new static();
    }

    public static function resourceNotInstantiable(Route $route, ReflectionClass $reflectionClass): static
    {
        return new static();
    }

    public static function cannotResolveUnionParameter(Route $route, mixed $parameter): static
    {
        return new static();
    }
}