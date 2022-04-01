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

        $sections = $context->isStringBased()
            ? explode($this->separator, $raw)
            : $raw;

        $errors = [];
        if ($this->elementParser) {
            foreach ($sections as $index => $section) {
                foreach ($this->elementParser->canParse($context->inherit($section)) as $error) {
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

        $sections = $context->isStringBased()
            ? explode($this->separator, $raw)
            : $raw;

        if (!$this->elementParser) {
            return $sections;
        }

        return array_map(function (string $section) use ($context) {
            return $this->elementParser->parse($context->inherit($section));
        }, $sections);
    }

    public function shouldParse(ParseContext $context): bool
    {
        if ($context->isStringBased()) {
            return true;
        }

        $values = $context->getValue();
        if (is_array($values)) {
            foreach ($values as $value) {
                if ($this->elementParser?->shouldParse($context->inherit($value))) {
                    return true;
                }
            }
        }

        return false;
    }

    public function setSeparator(string $separator): static
    {
        $this->separator = $separator;

        return $this;
    }
}