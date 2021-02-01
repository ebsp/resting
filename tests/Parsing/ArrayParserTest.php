<?php


namespace Seier\Resting\Tests\Parsing;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Parsing\IntParser;
use Seier\Resting\Parsing\ArrayParser;
use Seier\Resting\Parsing\IntParseError;
use Seier\Resting\Parsing\DefaultParseContext;
use Seier\Resting\Tests\Meta\AssertsErrors;

class ArrayParserTest extends TestCase
{

    use AssertsErrors;

    private ArrayParser $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new ArrayParser();
    }

    public function testWhenProvidedEmptyString()
    {
        $context = new DefaultParseContext('', isStringBased: true);
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertEquals([], $this->instance->parse($context));
    }

    public function testWhenProvidedNull()
    {
        $context = new DefaultParseContext(null, isStringBased: true);
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertEquals([], $this->instance->parse($context));
    }

    public function testDefaultSeparatorComma()
    {
        $context = new DefaultParseContext('a,b,c');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertEquals(['a', 'b', 'c'], $this->instance->parse($context));
    }

    public function testCustomSeparator()
    {
        $this->instance->setSeparator(':');

        $context = new DefaultParseContext('a:b:c');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertEquals(['a', 'b', 'c'], $this->instance->parse($context));
    }

    public function testCustomElementParsersCanCastArrayValues()
    {
        $this->instance->setElementParser(new IntParser);

        $context = new DefaultParseContext('1,2,3');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertEquals([1, 2, 3], $this->instance->parse($context));
    }

    public function testCustomElementParsersCanReturnErrors()
    {
        $this->instance->setElementParser(new IntParser);

        $context = new DefaultParseContext('1,a,3');
        $this->assertNotEmpty($errors = $this->instance->canParse($context));
        $this->assertHasError($errors, IntParseError::class, 1);
    }
}