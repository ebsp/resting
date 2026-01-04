<?php


namespace Seier\Resting\Tests\Parsing;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Parsing\TimeParser;
use Seier\Resting\Parsing\TimeParseError;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Parsing\DefaultParseContext;

class TimeParserTest extends TestCase
{

    use AssertsErrors;

    private TimeParser $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new TimeParser();
    }

    public function testWhenProvidedEmptyString()
    {
        $context = new DefaultParseContext('');
        $this->assertNotEmpty($errors = $this->instance->canParse($context));
        $this->assertHasError($errors, TimeParseError::class);
    }

    public function testCanParseTimeWithoutSeconds()
    {
        $context = new DefaultParseContext('10:11');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertNotNull($parsed = $this->instance->parse($context));
        $this->assertEquals(10, $parsed->hours);
        $this->assertEquals(11, $parsed->minutes);
        $this->assertEquals(0, $parsed->seconds);
    }

    public function testCannotParseTimeWithoutSecondsWhenRequired()
    {
        $this->instance->requireSeconds();

        $context = new DefaultParseContext('10:11');
        $this->assertNotEmpty($errors = $this->instance->canParse($context));
        $this->assertHasError($errors, TimeParseError::class);
    }

    public function testCanParseUsingCustomSeparator()
    {
        $this->instance->setSeparator('.');

        $context = new DefaultParseContext('10.11.12');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertNotNull($parsed = $this->instance->parse($context));
        $this->assertEquals(10, $parsed->hours);
        $this->assertEquals(11, $parsed->minutes);
        $this->assertEquals(12, $parsed->seconds);
    }

    public function testCanParseUsingCustomSeparatorWithoutSeconds()
    {
        $this->instance->setSeparator('.');

        $context = new DefaultParseContext('10.11');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertNotNull($parsed = $this->instance->parse($context));
        $this->assertEquals(10, $parsed->hours);
        $this->assertEquals(11, $parsed->minutes);
        $this->assertEquals(0, $parsed->seconds);
    }

    public function testCanParseWhenHourHasOneDigit()
    {
        $context = new DefaultParseContext('9:10');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertNotNull($parsed = $this->instance->parse($context));
        $this->assertEquals(9, $parsed->hours);
        $this->assertEquals(10, $parsed->minutes);
        $this->assertEquals(0, $parsed->seconds);
    }

    public function testCanParseWhenMinutesHasOneDigit()
    {
        $context = new DefaultParseContext('09:8');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertNotNull($parsed = $this->instance->parse($context));
        $this->assertEquals(9, $parsed->hours);
        $this->assertEquals(8, $parsed->minutes);
        $this->assertEquals(0, $parsed->seconds);
    }

    public function testCanParseWhenSecondsHasOneDigit()
    {
        $context = new DefaultParseContext('09:08:1');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertNotNull($parsed = $this->instance->parse($context));
        $this->assertEquals(9, $parsed->hours);
        $this->assertEquals(8, $parsed->minutes);
        $this->assertEquals(1, $parsed->seconds);
    }

    public function testCanParseDayStart()
    {
        $context = new DefaultParseContext('00:00:00');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertNotNull($parsed = $this->instance->parse($context));
        $this->assertEquals(0, $parsed->hours);
        $this->assertEquals(0, $parsed->minutes);
        $this->assertEquals(0, $parsed->seconds);
    }

    public function testCanParseDayEnd()
    {
        $context = new DefaultParseContext('23:59:59');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertNotNull($parsed = $this->instance->parse($context));
        $this->assertEquals(23, $parsed->hours);
        $this->assertEquals(59, $parsed->minutes);
        $this->assertEquals(59, $parsed->seconds);
    }

    public function testCannotParseWhenHoursExceed23()
    {
        $context = new DefaultParseContext('24:08:09');
        $this->assertNotEmpty($errors = $this->instance->canParse($context));
        $this->assertHasError($errors, TimeParseError::class);
    }

    public function testCannotParseWhenMinutesExceed59()
    {
        $context = new DefaultParseContext('23:60:09');
        $this->assertNotEmpty($errors = $this->instance->canParse($context));
        $this->assertHasError($errors, TimeParseError::class);
    }

    public function testCannotParseWhenSecondsExceed59()
    {
        $context = new DefaultParseContext('23:59:60');
        $this->assertNotEmpty($errors = $this->instance->canParse($context));
        $this->assertHasError($errors, TimeParseError::class);
    }
}