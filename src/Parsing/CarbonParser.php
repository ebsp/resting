<?php


namespace Seier\Resting\Parsing;


use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Seier\Resting\RestingSettings;
use Seier\Resting\Fields\EmptyStringAsNull;
use Carbon\Exceptions\InvalidFormatException;

class CarbonParser implements Parser
{

    use EmptyStringAsNull;

    public function canParse(ParseContext $context): array
    {
        $raw = $context->getValue();

        if ($raw === '' && $this->emptyStringAsNull) {
            return [];
        }

        if ($raw === '') {
            return [new CarbonParseError($raw)];
        }

        try {
            $this->carbonClass()::parse($raw);

            return [];
        } catch (InvalidFormatException) {
            return [new CarbonParseError($raw)];
        }
    }

    public function parse(ParseContext $context): Carbon|CarbonImmutable|null
    {
        $raw = $context->getValue();
        $raw = $this->maybeEmptyStringAsNull($raw);
        if ($raw === null) {
            return null;
        }

        return $this->carbonClass()::parse($raw);
    }

    /**
     * @return class-string<Carbon|CarbonImmutable>
     */
    private function carbonClass(): string
    {
        return RestingSettings::instance()->useImmutableCarbon
            ? CarbonImmutable::class
            : Carbon::class;
    }

    public function shouldParse(ParseContext $context): bool
    {
        return is_string($context->getValue());
    }
}
