<?php


namespace Seier\Resting\Parsing;


interface Parser
{

    public function shouldParse(ParseContext $context): bool;

    public function canParse(ParseContext $context): array;

    public function parse(ParseContext $context): mixed;
}