<?php

namespace Seier\Resting\Parsing;

use Seier\Resting\Exceptions\RestingRuntimeException;

class RawParser implements Parser
{

    public function canParse(ParseContext $context): array
    {
        throw new RestingRuntimeException('Unsupported RawParser::canParse');
    }

    public function parse(ParseContext $context): mixed
    {
        throw new RestingRuntimeException('Unsupported RawParser::parse');
    }

    public function shouldParse(ParseContext $context): bool
    {
        return false;
    }
}