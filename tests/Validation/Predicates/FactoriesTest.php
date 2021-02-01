<?php


namespace Seier\Resting\Tests\Validation\Predicates;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Fields\IntField;
use Seier\Resting\Fields\BoolField;
use Seier\Resting\Fields\StringField;
use Seier\Resting\Validation\Predicates\ArrayResourceContext;
use function Seier\Resting\Validation\Predicates\whenNull;
use function Seier\Resting\Validation\Predicates\whenEquals;
use function Seier\Resting\Validation\Predicates\whenNotNull;
use function Seier\Resting\Validation\Predicates\whenProvided;
use function Seier\Resting\Validation\Predicates\whenNotProvided;

class FactoriesTest extends TestCase
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

    private function context(array $data): ArrayResourceContext
    {
        return new ArrayResourceContext(
            $this->fields(),
            $data
        );
    }

    public function testWhenProvidedWhenValueIsProvided()
    {
        $context = $this->context(['string' => $this->faker->word]);
        $instance = whenProvided($this->string);

        $this->assertTrue($instance->passes($context));
    }

    public function testWhenProvidedWhenValueNotProvided()
    {
        $context = $this->context([]);
        $instance = whenProvided($this->string);

        $this->assertFalse($instance->passes($context));
    }

    public function testWhenProvidedWhenNullIsProvided()
    {
        $context = $this->context(['string' => null]);
        $instance = whenProvided($this->string);

        $this->assertTrue($instance->passes($context));
    }

    public function testWhenNotProvidedWhenValueIsProvided()
    {
        $context = $this->context(['string' => $this->faker->word]);
        $instance = whenNotProvided($this->string);

        $this->assertFalse($instance->passes($context));
    }

    public function testWhenNotProvidedWhenValueNotProvided()
    {
        $context = $this->context([]);
        $instance = whenNotProvided($this->string);

        $this->assertTrue($instance->passes($context));
    }

    public function testWhenNotProvidedWhenNullIsProvided()
    {
        $context = $this->context(['string' => null]);
        $instance = whenNotProvided($this->string);

        $this->assertFalse($instance->passes($context));
    }

    public function testWhenNullWhenValueIsProvided()
    {
        $context = $this->context(['string' => $this->faker->word]);
        $instance = whenNull($this->string);

        $this->assertFalse($instance->passes($context));
    }

    public function testWhenNullWhenValueNotProvided()
    {
        $context = $this->context([]);
        $instance = whenNull($this->string);

        $this->assertTrue($instance->passes($context));
    }

    public function testWhenNullWhenNullIsProvided()
    {
        $context = $this->context(['string' => null]);
        $instance = whenNull($this->string);

        $this->assertTrue($instance->passes($context));
    }

    public function testWhenNotNullWhenValueIsProvided()
    {
        $context = $this->context(['string' => $this->faker->word]);
        $instance = whenNotNull($this->string);

        $this->assertTrue($instance->passes($context));
    }

    public function testWhenNotNullWhenValueNotProvided()
    {
        $context = $this->context([]);
        $instance = whenNotNull($this->string);

        $this->assertFalse($instance->passes($context));
    }

    public function testWhenNotNullWhenNullIsProvided()
    {
        $context = $this->context(['string' => null]);
        $instance = whenNotNull($this->string);

        $this->assertFalse($instance->passes($context));
    }

    public function testWhenEqualsWhenEquals()
    {
        $context = $this->context(['string' => $expected = $this->faker->word]);
        $instance = whenEquals($this->string, $expected);

        $this->assertTrue($instance->passes($context));
    }

    public function testWhenEqualsWhenDoesNotEqual()
    {
        $context = $this->context(['string' => 'expected']);
        $instance = whenEquals($this->string, 'does not match');

        $this->assertFalse($instance->passes($context));
    }

    public function testWhenEqualsUsesStrictComparison()
    {
        $context = $this->context(['string' => '1']);
        $instance = whenEquals($this->string, 1);

        $this->assertFalse($instance->passes($context));
    }

    public function testWhenEqualsUsesParsedValueWhenParsable()
    {
        $context = $this->context(['int' => '1']);
        $instance = whenEquals($this->int, 1);

        $this->assertTrue($instance->passes($context));
    }

    public function testWhenEqualsReturnsFalseWhenCannotBeParsed()
    {
        $context = $this->context(['int' => 'not parsable']);
        $instance = whenEquals($this->int, 'not parable');

        $this->assertFalse($instance->passes($context));
    }
}