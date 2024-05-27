<?php

namespace Seier\Resting\Parsing;

use Exception;
use BackedEnum;
use ReflectionEnum;

class EnumParser implements Parser
{
    private ReflectionEnum $reflectionEnum;

    public function __construct(ReflectionEnum $reflectionEnum)
    {
        $this->reflectionEnum = $reflectionEnum;
    }

    public function canParse(ParseContext $context): array
    {
        $raw = $context->getValue();

        try {
            $this->reflectionEnum->getName()::from($raw);
            return [];
        } catch (Exception) {
            return [
                new EnumParseError($raw, $this->reflectionEnum),
            ];
        }
    }

    public function parse(ParseContext $context): BackedEnum
    {
        return $this->reflectionEnum->getName()::from($context->getValue());
    }

    public function shouldParse(ParseContext $context): bool
    {
        return $context->isStringBased() && $context->isNotNull();
    }
}
