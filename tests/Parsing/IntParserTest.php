<?php


namespace Seier\Resting\Tests\Parsing;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Parsing\IntParser;
use Seier\Resting\Parsing\IntParseError;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Parsing\DefaultParseContext;

class IntParserTest extends TestCase
{

    use AssertsErrors;

    private IntParser $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new IntParser();
    }

    public function testWhenProvidedEmptyString()
    {
        $context = new DefaultParseContext('');
        $this->assertNotEmpty($errors = $this->instance->canParse($context));
        $this->assertHasError($errors, IntParseError::class);
    }

    public function testCanParseZero()
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

    public function testCannotParseAlphaCharacters()
    {
        $context = new DefaultParseContext('a');
        $this->assertNotEmpty($errors = $this->instance->canParse($context));
        $this->assertHasError($errors, IntParseError::class);
    }

    public function testCannotParseAlphaCharactersEvenWhenStartingWithNumbers()
    {
        $context = new DefaultParseContext('1a');
        $this->assertNotEmpty($errors = $this->instance->canParse($context));
        $this->assertHasError($errors, IntParseError::class);
    }

    public function testCannotParseAlphaCharactersEvenWhenEndingWithNumbers()
    {
        $context = new DefaultParseContext('a1');
        $this->assertNotEmpty($errors = $this->instance->canParse($context));
        $this->assertHasError($errors, IntParseError::class);
    }
}