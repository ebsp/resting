<?php

namespace Seier\Resting\Parsing;

use Seier\Resting\Fields\Time;
use Seier\Resting\Fields\EmptyStringAsNull;

class TimeParser implements Parser
{
    use EmptyStringAsNull;

    private string $separator = ':';
    private bool $requireSeconds = false;

    public function requireSeconds(bool $state = true): static
    {
        $this->requireSeconds = $state;

        return $this;
    }

    public function canParse(ParseContext $context): array
    {
        $raw = $context->getValue();
        if ($raw === '' && $this->emptyStringAsNull) {
            return [];
        }

        $required = $this->requireSeconds ? '' : '?';
        $regex = "/^(2[0-3]|[01]?[0-9])\\$this->separator([0-5]?[0-9])(\\$this->separator([0-5]?[0-9])){$required}$/";

        return preg_match($regex, $raw)
            ? []
            : [new TimeParseError($raw)];
    }

    public function parse(ParseContext $context): ?Time
    {
        $raw = $this->maybeEmptyStringAsNull($context->getValue());
        if ($raw === null) {
            return null;
        }

        $sections = explode($this->separator, $raw);

        return new Time(
            hours: (int)$sections[0],
            minutes: (int)$sections[1],
            seconds: count($sections) === 3 ? (int)$sections[2] : 0,
        );
    }

    public function setSeparator(string $separator): static
    {
        $this->separator = $separator;

        return $this;
    }

    public function shouldParse(ParseContext $context): bool
    {
        $value = $context->getValue();

        return $value === null || is_string($context->getValue());
    }
}