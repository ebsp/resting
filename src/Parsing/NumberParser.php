<?php

namespace Seier\Resting\Parsing;

class NumberParser implements Parser
{
    private string $decimalSeparator = '.';

    public function canParse(ParseContext $context): array
    {
        $pattern = "/^-?[0-9]+(\\$this->decimalSeparator[0-9]+)?$/";
        $raw = $context->getValue();

        preg_match($pattern, $raw, $matches);

        return preg_match($pattern, $raw)
            ? []
            : [new NumberParseError($raw)];
    }

    public function parse(ParseContext $context): float
    {
        $raw = $context->getValue();

        return floatval(str_replace($this->decimalSeparator, '.', $raw));
    }

    public function setDecimalSeparator(string $separator): static
    {
        $this->decimalSeparator = $separator;

        return $this;
    }

    public function shouldParse(ParseContext $context): bool
    {
        return $context->isStringBased() && $context->isNotNull();
    }
}