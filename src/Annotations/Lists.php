<?php

namespace Seier\Resting\Annotations;

use Attribute;
use ReflectionFunctionAbstract;
use Seier\Resting\Resource;

#[Attribute(
    Attribute::TARGET_METHOD
    | Attribute::TARGET_FUNCTION
    | Attribute::IS_REPEATABLE
)]
class Lists
{
    /** @var class-string<Resource> */
    public readonly string $resource;

    /**
     * @param class-string<Resource> $resource
     */
    public function __construct(string $resource)
    {
        if (!is_subclass_of($resource, Resource::class)) {
            throw new \InvalidArgumentException(
                "#[Lists] expects a class-string of " . Resource::class . ", got '{$resource}'."
            );
        }

        $this->resource = $resource;
    }

    /**
     * @return class-string<Resource>[]
     */
    public static function resourcesFor(ReflectionFunctionAbstract $reflector): array
    {
        $resources = [];

        foreach ($reflector->getAttributes(self::class) as $attribute) {
            /** @var Lists $instance */
            $instance = $attribute->newInstance();
            $resources[] = $instance->resource;
        }

        return $resources;
    }
}
