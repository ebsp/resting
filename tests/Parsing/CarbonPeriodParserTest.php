<?php


namespace Seier\Resting\Tests\Parsing;


use Carbon\Carbon;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\Parsing\CarbonParser;
use Seier\Resting\Parsing\CarbonParseError;
use Seier\Resting\Parsing\CarbonPeriodParser;
use Seier\Resting\Parsing\DefaultParseContext;
use Seier\Resting\Parsing\CarbonPeriodParseError;
use Seier\Resting\Tests\Meta\AssertsErrors;

class CarbonPeriodParserTest extends TestCase
{

    use AssertsErrors;

    private CarbonPeriodParser $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new CarbonPeriodParser();
    }

    public function testWhenProvidedEmptyString()
    {
        $context = new DefaultParseContext('');
        $this->assertNotEmpty($errors = $this->instance->canParse($context));
        $this->assertHasError($errors, CarbonPeriodParseError::class);
    }

    public function testWhenProvidedTooManySeparators()
    {
        $context = new DefaultParseContext('2020-10-10,2020-10-11,');
        $this->assertNotEmpty($errors = $this->instance->canParse($context));
        $this->assertHasError($errors, CarbonPeriodParseError::class);
    }

    public function testCanParseDatePeriod()
    {
        $context = new DefaultParseContext('2020-10-10,2020-10-11');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertNotNull($parsed = $this->instance->parse($context));
        $this->assertEquals(Carbon::create(2020, 10, 10), $parsed->start);
        $this->assertEquals(Carbon::create(2020, 10, 11), $parsed->end);
    }

    public function testCanParseDatetimePeriod()
    {
        $context = new DefaultParseContext('2020-10-10 10:00:00,2020-10-11 11:00:00');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertNotNull($parsed = $this->instance->parse($context));
        $this->assertEquals(Carbon::create(2020, 10, 10, 10), $parsed->start);
        $this->assertEquals(Carbon::create(2020, 10, 11, 11), $parsed->end);
    }

    public function testCanParseWithCustomSeparator()
    {
        $this->instance->setSeparator(':');

        $context = new DefaultParseContext('2020-10-10:2020-10-11');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertNotNull($parsed = $this->instance->parse($context));
        $this->assertEquals(Carbon::create(2020, 10, 10), $parsed->start);
        $this->assertEquals(Carbon::create(2020, 10, 11), $parsed->end);
    }

    public function testWhenGivenUnknownStartFormat()
    {
        $context = new DefaultParseContext('unknown,2020-10-11');
        $this->assertNotEmpty($errors = $this->instance->canParse($context));
        $this->assertCount(1, $errors);
        $this->assertHasError($errors, CarbonParseError::class, 'start');
    }

    public function testWhenGivenUnknownEndFormat()
    {
        $context = new DefaultParseContext('2020-10-10,unknown');
        $this->assertNotEmpty($errors = $this->instance->canParse($context));
        $this->assertCount(1, $errors);
        $this->assertHasError($errors, CarbonParseError::class, 'end');
    }

    public function testCarbonParsersCanReturnErrors()
    {
        $this->instance->onStart(function (CarbonParser $start) {
            $start->withFormat('Y-m-d H');
        });

        $context = new DefaultParseContext('2020-10-11,2020-10-12');
        $this->assertNotEmpty($errors = $this->instance->canParse($context));
        $this->assertHasError($errors, CarbonParseError::class, 'start');
    }

    public function testCarbonParsersCanParseReturnErrors()
    {
        $this->instance->onStart(function (CarbonParser $start) {
            $start->withFormat('Y-m-d H');
        });

        $context = new DefaultParseContext('2020-10-11 14,2020-10-12');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertEquals(Carbon::create(2020, 10, 11, 14), $this->instance->parse($context)->start);
    }
}