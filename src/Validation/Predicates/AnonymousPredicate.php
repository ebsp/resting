<?php


namespace Seier\Resting\Validation\Predicates;


use Closure;

class AnonymousPredicate implements Predicate
{

    private Closure $describer;
    private Closure $evaluator;

    protected function __construct(Closure $describer, Closure $evaluator)
    {
        $this->describer = $describer;
        $this->evaluator = $evaluator;
    }

    public static function of(Closure $describer, Closure $evaluator): static
    {
        return new static($describer, $evaluator);
    }

    public function description(ResourceContext $context): string
    {
        return ($this->describer)($context);
    }

    public function passes(ResourceContext $context): bool
    {
        return ($this->evaluator)($context);
    }
}