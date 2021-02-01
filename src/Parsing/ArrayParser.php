<?php


namespace Seier\Resting\Parsing;


class ArrayParser implements Parser
{

    private string $separator = ',';
    private ?Parser $elementParser = null;

    public function setElementParser(Parser $elementParser): void
    {
        $this->elementParser = $elementParser;
    }

    public function canParse(ParseContext $context): array
    {
        $raw = $context->getValue();
        if ($context->isStringBased() && empty($raw)) {
            return [];
        }

        $sections = explode($this->separator, $raw);
        $errors = [];

        if ($this->elementParser) {
            foreach ($sections as $index => $section) {
                foreach ($this->elementParser->canParse(new DefaultParseContext($section, $context->isStringBased())) as $error) {
                    $errors[] = $error->prependPath($index);
                }
            }
        }

        return $errors;
    }

    public function parse(ParseContext $context): array
    {
        $raw = $context->getValue();
        if ($context->isStringBased() && empty($raw)) {
            return [];
        }

        $sections = explode($this->separator, $raw);

        if (!$this->elementParser) {
            return $sections;
        }

        return array_map(function (string $section) use ($context) {
            return $this->elementParser->parse(new DefaultParseContext($section, $context->isStringBased()));
        }, $sections);
    }

    public function shouldParse(ParseContext $context): bool
    {
        return $context->isStringBased();
    }

    public function setSeparator(string $separator): static
    {
        $this->separator = $separator;

        return $this;
    }
}