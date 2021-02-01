<?php


namespace Seier\Resting\Parsing;


class BoolParser implements Parser
{

    private array $mappings = [
        '1' => true,
        '0' => false,
        'true' => true,
        'false' => false,
    ];

    public function canParse(ParseContext $context): array
    {
        $raw = $context->getValue();
        return array_key_exists($raw, $this->mappings)
            ? []
            : [new BoolParseError(array_keys($this->mappings), $raw)];
    }

    public function parse(ParseContext $context): bool
    {
        $raw = $context->getValue();

        return (bool)$this->mappings[$raw];
    }

    public function withMapping(string $from, bool $to): static
    {
        $this->mappings[$from] = $to;

        return $this;
    }

    public function shouldParse(ParseContext $context): bool
    {
        return $context->isStringBased();
    }

    public function setMappings(array $mappings): static
    {
        $this->mappings = $mappings;

        return $this;
    }
}