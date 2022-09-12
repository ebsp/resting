<?php

namespace Seier\Resting\Validation\Resolver;

use Closure;
use Seier\Resting\Fields\Field;
use Seier\Resting\Validation\Predicates\ResourceContext;

class ClosureValidatorResolver implements ValidatorResolver
{
    private Closure $closure;

    public function __construct(Closure $closure)
    {
        $this->closure = $closure;
    }

    public static function whenNotNullThen(Field $other, Closure $factory): static
    {
        return new ClosureValidatorResolver(
            function (ResourceContext $context) use ($factory, $other) {
                return $context->isNull($other) || !$context->canBeParsed($other)
                    ? []
                    : [$factory($context->getValue($other))];
            }
        );
    }

    public function resolve(ResourceContext $context): array
    {
        return ($this->closure)($context);
    }
}