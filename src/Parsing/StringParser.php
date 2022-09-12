<?php

namespace Seier\Resting\Parsing;

use Seier\Resting\Fields\EmptyStringAsNull;

class StringParser implements Parser
{
    use EmptyStringAsNull;

    public function canParse(ParseContext $context): array
    {
        return [];
    }

    public function parse(ParseContext $context): ?string
    {
        return $this->maybeEmptyStringAsNull($context->getValue());
    }

    public function shouldParse(ParseContext $context): bool
    {
        return $this->emptyStringAsNull;
    }
}