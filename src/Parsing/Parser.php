<?php

namespace Seier\Resting\Parsing;

/**
 * @method mixed shouldParse(ParseContext $context)
 * @method mixed canParse(ParseContext $context)
 * @method mixed parse(ParseContext $context)
 */
interface Parser
{
    /**
     * Determine wether or not a value should be parsed
     *
     * @param ParseContext $context
     * @return boolean
     */
    public function shouldParse(ParseContext $context): bool;

    /**
     * Determine wether or not a value fits this parser
     *
     * @param ParseContext $context
     * @return array
     */
    public function canParse(ParseContext $context): array;

    /**
     * Parse the value
     *
     * @param ParseContext $context
     * @return mixed
     */
    public function parse(ParseContext $context): mixed;
}
