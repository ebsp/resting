<?php


namespace Seier\Resting\Parsing;


class StringParser implements Parser
{

    public function canParse(ParseContext $context): array
    {
        return [];
    }

    public function parse(ParseContext $context): string
    {
        return $context->getValue();
    }

    public function shouldParse(ParseContext $context): bool
    {
        return false;
    }
}