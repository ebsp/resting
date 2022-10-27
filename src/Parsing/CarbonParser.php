<?php

namespace Seier\Resting\Parsing;

use Carbon\Carbon;
use Seier\Resting\Fields\EmptyStringAsNull;
use Carbon\Exceptions\InvalidFormatException;

class CarbonParser implements Parser
{
    use EmptyStringAsNull;

    private ?string $format = null;

    public function withFormat(?string $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function canParse(ParseContext $context): array
    {
        $raw = $context->getValue();

        if ($raw === '' && $this->emptyStringAsNull) {
            return [];
        }

        if ($raw === '') {
            return [new CarbonParseError($this->format, $raw)];
        }

        try {

            if ($this->format) {
                Carbon::createFromFormat($this->format, $raw);
            } else {
                Carbon::parse($raw);
            }

            return [];
        } catch (InvalidFormatException) {
            return [new CarbonParseError($this->format, $raw)];
        }
    }

    public function parse(ParseContext $context): ?Carbon
    {
        $raw = $context->getValue();
        $raw = $this->maybeEmptyStringAsNull($raw);
        if ($raw === null) {
            return null;
        }

        return $this->format
            ? Carbon::createFromFormat($this->format, $raw, now()->timezone)
            : Carbon::parse($raw);
    }

    public function shouldParse(ParseContext $context): bool
    {
        return is_string($context->getValue());
    }
}