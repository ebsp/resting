<?php


namespace Seier\Resting\Tests\Parsing;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Parsing\BoolParser;
use Seier\Resting\Parsing\BoolParseError;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Parsing\DefaultParseContext;

class BoolParserTest extends TestCase
{

    use AssertsErrors;

    private BoolParser $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new BoolParser();
    }

    public function testWhenProvidedEmptyString()
    {
        $context = new DefaultParseContext('');
        $this->assertNotEmpty($errors = $this->instance->canParse($context));
        $this->assertHasError($errors, BoolParseError::class);
    }

    public function testWhenProvidedZero()
    {
        $context = new DefaultParseContext('0');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertFalse($this->instance->parse($context));
    }

    public function testWhenProvidedOne()
    {
        $context = new DefaultParseContext('1');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertTrue($this->instance->parse($context));
    }

    public function testWhenProvidedTrue()
    {
        $context = new DefaultParseContext('false');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertFalse($this->instance->parse($context));
    }

    public function testWhenProvidedFalse()
    {
        $context = new DefaultParseContext('true');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertTrue($this->instance->parse($context));
    }

    public function testWhenProvidedOtherPositiveNumber()
    {
        $context = new DefaultParseContext('2');
        $this->assertNotEmpty($errors = $this->instance->canParse($context));
        $this->assertHasError($errors, BoolParseError::class);
    }

    public function testWhenProvidedRandomString()
    {
        $context = new DefaultParseContext('random');
        $this->assertNotEmpty($errors = $this->instance->canParse($context));
        $this->assertHasError($errors, BoolParseError::class);
    }

    public function testWithMappingCanAddNewMappings()
    {
        $this->instance->withMapping($on = 'on', true);
        $this->instance->withMapping($off = 'off', false);

        $onContext = new DefaultParseContext($on);
        $offContext = new DefaultParseContext($off);

        $this->assertEmpty($this->instance->canParse($onContext));
        $this->assertTrue($this->instance->parse($onContext));

        $this->assertEmpty($this->instance->canParse($offContext));
        $this->assertFalse($this->instance->parse($offContext));
    }

    public function testWithMappingDoesNotRemoveExistingMappings()
    {
        $this->instance->withMapping('on', true);

        $context = new DefaultParseContext('1');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertTrue($this->instance->parse($context));
    }

    public function testSetMappingsOverwritesExistingMappings()
    {
        $this->instance->setMappings(['on' => true]);

        $context = new DefaultParseContext('on');

        $this->assertEmpty($this->instance->canParse($context));
        $this->assertTrue($this->instance->parse($context));

        $this->assertNotEmpty($errors = $this->instance->canParse(new DefaultParseContext('1')));
        $this->assertHasError($errors, BoolParseError::class);
    }
}