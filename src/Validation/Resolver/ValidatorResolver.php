<?php


namespace Seier\Resting\Validation\Resolver;


use Seier\Resting\Validation\Predicates\ResourceContext;

interface ValidatorResolver
{

    public function resolve(ResourceContext $context): array;
}