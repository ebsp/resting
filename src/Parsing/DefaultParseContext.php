<?php


namespace Seier\Resting\Parsing;


class DefaultParseContext implements ParseContext
{

    private mixed $value;
    private bool $isStringBased;

    public function __construct(mixed $value, bool $isStringBased = true)
    {
        $this->value = $value;
        $this->isStringBased = $isStringBased;
    }

    public function isStringBased(): bool
    {
        return $this->isStringBased;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function isNull(): bool
    {
        return $this->value === null;
    }

    public function isNotNull(): bool
    {
        return !$this->isNull();
    }
}