<?php


namespace Seier\Resting;


use Closure;
use Seier\Resting\Resource as RestingResource;

class ClosureResourceFactory implements ResourceFactory
{

    private Closure $factory;

    protected function __construct(Closure $factory)
    {
        $this->factory = $factory;
    }

    public static function from(string|Closure $type): static
    {
        if (is_string($type)) {
            $type = function () use ($type) {
                return new $type;
            };
        }

        return new static($type);
    }

    public function create(): RestingResource
    {
        return ($this->factory)();
    }
}