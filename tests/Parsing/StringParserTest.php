<?php


namespace Seier\Resting\Tests\Parsing;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Parsing\StringParser;
use Seier\Resting\Parsing\DefaultParseContext;

class StringParserTest extends TestCase
{

    private StringParser $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new StringParser();
    }

    public function testCanParseEmptyString()
    {
        $context = new DefaultParseContext($expected = '');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertEquals($expected, $this->instance->parse($context));
    }

    public function testCanParseWords()
    {
        $context = new DefaultParseContext($expected = $this->faker->word);
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertEquals($expected, $this->instance->parse($context));
    }
}