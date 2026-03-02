<?php


namespace Seier\Resting\Tests\Parsing;


use Carbon\Carbon;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\Parsing\CarbonParser;
use Seier\Resting\Parsing\CarbonParseError;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Parsing\DefaultParseContext;

class CarbonParserTest extends TestCase
{

    use AssertsErrors;

    private CarbonParser $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new CarbonParser();
    }

    public function testWhenProvidedEmptyString()
    {
        $context = new DefaultParseContext('');
        $this->assertNotEmpty($errors = $this->instance->canParse($context));
        $this->assertHasError($errors, CarbonParseError::class);
    }

    public function testWhenProvidedIncorrectFormat()
    {
        $context = new DefaultParseContext('incorrect');
        $this->assertNotEmpty($errors = $this->instance->canParse($context));
        $this->assertHasError($errors, CarbonParseError::class);
    }

    public function testWhenProvidedDateFormat()
    {
        $context = new DefaultParseContext('2020-10-11');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertEquals(Carbon::create(2020, 10, 11), $this->instance->parse($context));
    }

    public function testWhenProvidedDatetimeFormat()
    {
        $context = new DefaultParseContext('2020-10-11 10:11:12');
        $this->assertEmpty($this->instance->canParse($context));
        $this->assertEquals(Carbon::create(2020, 10, 11, 10, 11, 12), $this->instance->parse($context));
    }

    public function testCanEnforceFormat()
    {
        $this->instance->withFormat('Y-m-d');

        $context = new DefaultParseContext('2020-10-11 10:11:12');
        $this->assertNotEmpty($errors = $this->instance->canParse($context));
        $this->assertHasError($errors, CarbonParseError::class);
    }
}