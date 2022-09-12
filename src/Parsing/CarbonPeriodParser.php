<?php

namespace Seier\Resting\Parsing;

use Carbon\CarbonPeriod;
use Seier\Resting\Validation\Errors\ValidationError;

class CarbonPeriodParser implements Parser
{
    private string $separator = ',';
    private CarbonParser $startParser;
    private CarbonParser $endParser;

    public function __construct()
    {
        $this->startParser = new CarbonParser();
        $this->endParser = new CarbonParser();
    }

    public function canParse(ParseContext $context): array
    {
        $raw = $context->getValue();
        $sections = $raw === '' ? [] : explode($this->separator, $raw);

        return $this->canParseFromArrayOfStrings($context, $sections);
    }

    public function parse(ParseContext $context): CarbonPeriod
    {
        $raw = $context->getValue();
        $sections = explode($this->separator, $raw);

        return $this->parseFromArrayOfStrings($context, $sections);
    }

    public function shouldParse(ParseContext $context): bool
    {
        return $context->isStringBased() && $context->isNotNull();
    }

    public function canParseFromArrayOfStrings(ParseContext $context, array $strings): array
    {
        $count = count($strings);

        if ($count > 2) {
            return [CarbonPeriodParseError::tooManyArguments()];
        }

        if ($count < 1) {
            return [CarbonPeriodParseError::tooFewArguments()];
        }

        $errors = [];
        $errors = array_merge($errors, array_map(function (ValidationError $error) {
            return $error->prependPath('start');
        }, $this->startParser->canParse(new DefaultParseContext($strings[0], $context->isStringBased()))));

        if ($count > 1) {
            $errors = array_merge($errors, array_map(function (ValidationError $error) {
                return $error->prependPath('end');
            }, $this->endParser->canParse(new DefaultParseContext($strings[1], $context->isStringBased()))));
        }

        return $errors;
    }

    public function parseFromArrayOfStrings(ParseContext $context, array $strings): CarbonPeriod
    {
        $count = count($strings);
        $parsed = [];

        $parsed[] = $this->startParser->parse(new DefaultParseContext($strings[0], true));
        if ($count > 1) {
            $parsed[] = $this->endParser->parse(new DefaultParseContext($strings[1], true));
        }

        return CarbonPeriod::create(...$parsed);
    }

    public function setSeparator(string $separator): static
    {
        $this->separator = $separator;

        return $this;
    }

    public function setStartParser(CarbonParser $startParser): static
    {
        $this->startParser = $startParser;

        return $this;
    }

    public function setEndParser(CarbonParser $endParser): static
    {
        $this->endParser = $endParser;

        return $this;
    }

    public function onStart(callable $callable): static
    {
        $callable($this->startParser);

        return $this;
    }

    public function onEnd(callable $callable): static
    {
        $callable($this->endParser);

        return $this;
    }
}