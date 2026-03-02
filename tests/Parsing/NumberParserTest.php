<?php


namespace Seier\Resting\Tests\Parsing;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Parsing\NumberParser;
use Seier\Resting\Parsing\NumberParseError;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Parsing\DefaultParseContext;

class NumberParserTest extends TestCase
{

    use AssertsErrors;

    private NumberParser $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new NumberParser();
    }

    public function testWhenProvidedEmptyString()
    {
        $context = new DefaultParseContext('');
        $this->assertNotEmpty($errors = $this->instance->canParse($context));
        $this->assertHasError($errors, NumberParseError::class);
    }

    public function testCanParseZeroInteger()
    {
        $context = new DefaultParseContext('0');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertEquals(0, $this->instance->parse($context));
    }

    public function testCanParsePositiveIntegers()
    {
        $context = new DefaultParseContext('4');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertEquals(4, $this->instance->parse($context));
    }

    public function testCanParseNegativeIntegers()
    {
        $context = new DefaultParseContext('-2');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertEquals(-2, $this->instance->parse($context));
    }

    public function testCanParseZeroFloat()
    {
        $context = new DefaultParseContext('0.0');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertEquals(0.0, $this->instance->parse($context));
    }

    public function testCanParsePositiveFloat()
    {
        $context = new DefaultParseContext('1.5');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertEquals(1.5, $this->instance->parse($context));
    }

    public function testCanParseNegativeFloat()
    {
        $context = new DefaultParseContext('-1.5');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertEquals(-1.5, $this->instance->parse($context));
    }

    public function testCanParseUsingCustomSeparator()
    {
        $this->instance->setDecimalSeparator(',');

        $context = new DefaultParseContext('1,98');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertEquals(1.98, $this->instance->parse($context));
    }

    public function testCannotParseAlphaCharacters()
    {
        $context = new DefaultParseContext('a');
        $this->assertNotEmpty($errors = $this->instance->canParse($context));
        $this->assertHasError($errors, NumberParseError::class);
    }

    public function testCannotParseAlphaCharactersEvenWhenStartingWithNumbers()
    {
        $context = new DefaultParseContext('1a');
        $this->assertNotEmpty($errors = $this->instance->canParse($context));
        $this->assertHasError($errors, NumberParseError::class);
    }

    public function testCannotParseAlphaCharactersEvenWhenEndingWithNumbers()
    {
        $context = new DefaultParseContext('a1');
        $this->assertNotEmpty($errors = $this->instance->canParse($context));
        $this->assertHasError($errors, NumberParseError::class);
    }
}