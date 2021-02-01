<?php


namespace Seier\Resting\Tests\Validation\Predicates;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Fields\IntField;
use Seier\Resting\Fields\BoolField;
use Seier\Resting\Fields\StringField;
use Seier\Resting\Validation\Predicates\ArrayResourceContext;

class ArrayResourceContextTest extends TestCase
{

    private StringField $string;
    private IntField $int;
    private BoolField $bool;

    public function setUp(): void
    {
        parent::setUp();

        $this->string = new StringField();
        $this->int = new IntField();
        $this->bool = new BoolField();
    }

    private function fields(): array
    {
        return [
            'string' => $this->string,
            'int' => $this->int,
            'bool' => $this->bool,
        ];
    }

    private function make(array $data): ArrayResourceContext
    {
        return new ArrayResourceContext(
            $this->fields(),
            $data
        );
    }

    public function testGetValueWhenProvidedValue()
    {
        $instance = $this->make([
            'string' => $stringValue = $this->faker->word,
            'int' => $intValue = $this->faker->randomNumber(2),
            'bool' => $boolValue = $this->faker->boolean,
        ]);

        $this->assertEquals($stringValue, $instance->getValue($this->string));
        $this->assertEquals($intValue, $instance->getValue($this->int));
        $this->assertEquals($boolValue, $instance->getValue($this->bool));
    }

    public function testGetValueWhenNotProvidedValue()
    {
        $instance = $this->make([]);

        $this->assertNull($instance->getValue($this->string));
        $this->assertNull($instance->getValue($this->int));
        $this->assertNull($instance->getValue($this->bool));
    }

    public function testGetValueWhenProvidedNull()
    {
        $instance = $this->make(['string' => null]);

        $this->assertNull($instance->getValue($this->string));
    }

    public function testWasProvidedWhenProvided()
    {
        $instance = $this->make(['string' => $this->faker->word]);

        $this->assertTrue($instance->wasProvided($this->string));
    }

    public function testWasProvidedWhenNotProvided()
    {
        $instance = $this->make([]);

        $this->assertFalse($instance->wasProvided($this->string));
        $this->assertFalse($instance->wasProvided($this->int));
        $this->assertFalse($instance->wasProvided($this->bool));
    }

    public function testWasProvidedWhenProvidedNull()
    {
        $instance = $this->make(['string' => null]);

        $this->assertTrue($instance->wasProvided($this->string));
    }

    public function testGetName()
    {
        $instance = $this->make([]);

        $this->assertEquals('string', $instance->getName($this->string));
        $this->assertEquals('int', $instance->getName($this->int));
        $this->assertEquals('bool', $instance->getName($this->bool));
    }

    public function testIsNullWhenProvidedValue()
    {
        $instance = $this->make(['string' => $this->faker->word]);

        $this->assertFalse($instance->isNull($this->string));
    }

    public function testIsNullWhenProvidedNull()
    {
        $instance = $this->make(['string' => null]);

        $this->assertTrue($instance->isNull($this->string));
    }

    public function testIsNullWhenNotProvided()
    {
        $instance = $this->make([]);

        $this->assertTrue($instance->isNull($this->string));
    }

    public function testCanBeParsedWhenValueCanBeParsed()
    {
        $instance = $this->make(['int' => '1']);

        $this->assertTrue($instance->canBeParsed($this->int));
    }

    public function testCanBeParsedWhenValueCannotBeParsed()
    {
        $instance = $this->make(['int' => 'cannot be parsed']);

        $this->assertFalse($instance->canBeParsed($this->int));
    }

    public function testGetRawValueWhenParsable()
    {
        $instance = $this->make(['int' => '1']);

        $this->assertSame('1', $instance->getRawValue($this->int));
    }

    public function testGetRawValueWhenNotParsable()
    {
        $instance = $this->make(['int' => 'not parsable']);

        $this->assertSame('not parsable', $instance->getRawValue($this->int));
    }

    public function testGetValueWhenParsable()
    {
        $instance = $this->make(['int' => '1']);

        $this->assertSame(1, $instance->getValue($this->int));
    }

    public function testGetValueWhenNotParsable()
    {
        $instance = $this->make(['int' => '1']);

        $this->assertSame('1', $instance->getRawValue($this->int));
    }
}