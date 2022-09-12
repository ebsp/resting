<?php

namespace Seier\Resting\Parsing;

class IntParser implements Parser
{
    public function canParse(ParseContext $context): array
    {
        $raw = $context->getValue();

        return preg_match('/^-?[0-9]+$/', $raw)
            ? []
            : [new IntParseError($raw)];
    }

    public function parse(ParseContext $context): int
    {
        return (int)$context->getValue();
    }

    public function shouldParse(ParseContext $context): bool
    {
        return $context->isStringBased() && $context->isNotNull();
    }
}