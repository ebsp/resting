<?php


namespace Seier\Resting\Parsing;


interface ParseContext
{

    public function isStringBased(): bool;

    public function getValue(): mixed;

    public function isNull(): bool;

    public function isNotNull(): bool;
}