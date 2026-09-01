<?php


namespace Seier\Resting\Parsing;


use Seier\Resting\Fields\EmptyStringAsNull;

class StringParser implements Parser
{

    use EmptyStringAsNull;

    protected bool $trimmed = true;

    public function canParse(ParseContext $context): array
    {
        return [];
    }

    public function parse(ParseContext $context): mixed
    {
        $value = $context->getValue();

        if ($this->trimmed && is_string($value)) {
            $value = trim($value);
        }

        return $this->maybeEmptyStringAsNull($value);
    }

    public function shouldParse(ParseContext $context): bool
    {
        return $this->trimmed || $this->emptyStringAsNull;
    }

    /**
     * Instructs the parser whether or not to trim the parsed strings. Strings are trimmed by default.
     *
     * @param bool $state Whether or not the parsed strings should be trimmed.
     * @return $this
     */
    public function trim(bool $state = true): static
    {
        $this->trimmed = $state;

        return $this;
    }

    public function isTrimmed(): bool
    {
        return $this->trimmed;
    }
}
