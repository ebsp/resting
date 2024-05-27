<?php


namespace Seier\Resting\Tests\Validation\Predicates;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Fields\IntField;
use Seier\Resting\Fields\BoolField;
use Seier\Resting\Fields\EnumField;
use Seier\Resting\Fields\StringField;
use Seier\Resting\Tests\Meta\SuiteEnum;
use Seier\Resting\Validation\Predicates\Predicate;
use Seier\Resting\Validation\Predicates\ResourceContext;
use Seier\Resting\Validation\Predicates\ArrayResourceContext;
use function Seier\Resting\Validation\Predicates\any;
use function Seier\Resting\Validation\Predicates\all;
use function Seier\Resting\Validation\Predicates\none;
use function Seier\Resting\Validation\Predicates\whenIn;
use function Seier\Resting\Validation\Predicates\whenNull;
use function Seier\Resting\Validation\Predicates\whenNotIn;
use function Seier\Resting\Validation\Predicates\whenEquals;
use function Seier\Resting\Validation\Predicates\when;
use function Seier\Resting\Validation\Predicates\whenNotNull;
use function Seier\Resting\Validation\Predicates\whenProvided;
use function Seier\Resting\Validation\Predicates\whenNotEquals;
use function Seier\Resting\Validation\Predicates\whenNotProvided;

class FactoriesTest extends TestCase
{

    private StringField $string;
    private IntField $int;
    private BoolField $bool;
    private EnumField $enum;

    public function setUp(): void
    {
        parent::setUp();

        $this->string = new StringField();
        $this->int = new IntField();
        $this->bool = new BoolField();
        $this->enum = new EnumField(SuiteEnum::class);
    }

    private function fields(): array
    {
        return [
            'string' => $this->string,
            'int' => $this->int,
            'bool' => $this->bool,
            'enum' => $this->enum,
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

    public function testWhenProvidedManyWhenSomeAreProvided()
    {
        $context = $this->context(['string' => null]);
        $instance = whenProvided($this->string, $this->int);

        $this->assertFalse($instance->passes($context));
    }

    public function testWhenProvidedManyWhenAllAreProvided()
    {
        $context = $this->context(['string' => null, 'int' => null]);
        $instance = whenProvided($this->string, $this->int);

        $this->assertTrue($instance->passes($context));
    }

    public function testWhenProvidedManyWhenNoneAreProvided()
    {
        $context = $this->context([]);
        $instance = whenProvided($this->string, $this->int);

        $this->assertFalse($instance->passes($context));
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

    public function testWhenNotProvidedManyWhenSomeAreProvided()
    {
        $context = $this->context(['string' => null]);
        $instance = whenNotProvided($this->string, $this->int);

        $this->assertFalse($instance->passes($context));
    }

    public function testWhenNotProvidedManyWhenAllAreProvided()
    {
        $context = $this->context(['string' => null, 'int' => null]);
        $instance = whenNotProvided($this->string, $this->int);

        $this->assertFalse($instance->passes($context));
    }

    public function testWhenNotProvidedManyWhenNoneAreProvided()
    {
        $context = $this->context([]);
        $instance = whenNotProvided($this->string, $this->int);

        $this->assertTrue($instance->passes($context));
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

    public function testWhenNullManyWhenSomeAreNull()
    {
        $context = $this->context(['string' => null, 'int' => 1]);
        $instance = whenNull($this->string, $this->int);

        $this->assertFalse($instance->passes($context));
    }

    public function testWhenNullManyWhenAllAreNull()
    {
        $context = $this->context(['string' => null, 'int' => null]);
        $instance = whenNull($this->string, $this->int);

        $this->assertTrue($instance->passes($context));
    }

    public function testWhenNullManyWhenNoneAreNull()
    {
        $context = $this->context(['string' => 'a', 'int' => 1]);
        $instance = whenNull($this->string, $this->int);

        $this->assertFalse($instance->passes($context));
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

    public function testWhenNotNullManyWhenSomeAreNull()
    {
        $context = $this->context(['string' => null, 'int' => 1]);
        $instance = whenNotNull($this->string, $this->int);

        $this->assertFalse($instance->passes($context));
    }

    public function testWhenNotNullManyWhenAllAreNull()
    {
        $context = $this->context(['string' => null, 'int' => null]);
        $instance = whenNotNull($this->string, $this->int);

        $this->assertFalse($instance->passes($context));
    }

    public function testWhenNotNullManyWhenNoneAreNull()
    {
        $context = $this->context(['string' => '', 'int' => 1]);
        $instance = whenNotNull($this->string, $this->int);

        $this->assertTrue($instance->passes($context));
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

    public function testWhenNotEqualsWhenEqual()
    {
        $context = $this->context(['string' => $expected = $this->faker->word]);
        $instance = whenNotEquals($this->string, $expected);

        $this->assertFalse($instance->passes($context));
    }

    public function testWhenNotEqualsWhenNotNotEqual()
    {
        $context = $this->context(['string' => 'expected']);
        $instance = whenNotEquals($this->string, 'does not match');

        $this->assertTrue($instance->passes($context));
    }

    public function testWhenNotEqualsUsesStrictComparison()
    {
        $context = $this->context(['string' => '1']);
        $instance = whenNotEquals($this->string, 1);

        $this->assertTrue($instance->passes($context));
    }

    public function testWhenNotEqualsUsesParsedValueWhenParsable()
    {
        $context = $this->context(['int' => '1']);
        $instance = whenNotEquals($this->int, 1);

        $this->assertFalse($instance->passes($context));
    }

    public function testWhenNotEqualsReturnsFalseWhenCannotBeParsed()
    {
        $context = $this->context(['int' => 'not parsable']);
        $instance = whenNotEquals($this->int, 'not parable');

        $this->assertTrue($instance->passes($context));
    }

    public function testWhenInWhenMatching()
    {
        $context = $this->context(['string' => $expected = $this->faker->word]);
        $instance = whenNotEquals($this->string, [$expected, 1, 2]);

        $this->assertTrue($instance->passes($context));
    }

    public function testWhenInWhenNotMatching()
    {
        $context = $this->context(['string' => $this->faker->word]);
        $instance = whenIn($this->string, [1, 2]);

        $this->assertFalse($instance->passes($context));
    }

    public function testWhenInWhenOptionsAreEmpty()
    {
        $context = $this->context(['string' => $this->faker->word]);
        $instance = whenIn($this->string, []);

        $this->assertFalse($instance->passes($context));
    }

    public function testWhenInUsesStrictComparison()
    {
        $context = $this->context(['string' => '1']);
        $instance = whenIn($this->string, [1]);

        $this->assertFalse($instance->passes($context));
    }

    public function testWhenInUsesParsedValueWhenParsable()
    {
        $context = $this->context(['int' => '1']);
        $instance = whenIn($this->int, [1]);

        $this->assertTrue($instance->passes($context));
    }

    public function testWhenInReturnsFalseWhenCannotBeParsed()
    {
        $context = $this->context(['int' => 'not parsable']);
        $instance = whenIn($this->int, ['not parable']);

        $this->assertFalse($instance->passes($context));
    }

    public function testWhenNotInWhenMatching()
    {
        $context = $this->context(['string' => $expected = $this->faker->word]);
        $instance = whenNotIn($this->string, [$expected, 1, 2]);

        $this->assertFalse($instance->passes($context));
    }

    public function testWhenNotInWhenNotMatching()
    {
        $context = $this->context(['string' => $this->faker->word]);
        $instance = whenNotIn($this->string, [1, 2]);

        $this->assertTrue($instance->passes($context));
    }

    public function testWhenNotInWhenOptionsAreEmpty()
    {
        $context = $this->context(['string' => $this->faker->word]);
        $instance = whenNotIn($this->string, []);

        $this->assertTrue($instance->passes($context));
    }

    public function testWhenNotInUsesStrictComparison()
    {
        $context = $this->context(['string' => '1']);
        $instance = whenNotIn($this->string, [1]);

        $this->assertTrue($instance->passes($context));
    }

    public function testWhenNotInUsesParsedValueWhenParsable()
    {
        $context = $this->context(['int' => '1']);
        $instance = whenNotIn($this->int, [1]);

        $this->assertFalse($instance->passes($context));
    }

    public function testWhenNotInReturnsFalseWhenCannotBeParsed()
    {
        $context = $this->context(['int' => 'not parsable']);
        $instance = whenNotIn($this->int, ['not parsable']);

        $this->assertTrue($instance->passes($context));
    }

    public function testWhenPassesForInt()
    {
        $instance = when($this->int, function (int $value) {
            return $value === 5;
        });

        $this->assertTrue($instance->passes($this->context(['int' => '5'])));
        $this->assertTrue($instance->passes($this->context(['int' => 5])));
        $this->assertFalse($instance->passes($this->context(['int' => 1])));
        $this->assertFalse($instance->passes($this->context(['int' => 'not parsable'])));
    }

    public function testWhenPassesForParsableEnum()
    {
        $instance = when($this->enum, function (SuiteEnum $value) {
            return $value === SuiteEnum::Clubs;
        });

        $this->assertTrue($instance->passes($this->context(['enum' => SuiteEnum::Clubs->value])));
        $this->assertFalse($instance->passes($this->context(['enum' => SuiteEnum::Diamonds->value])));
        $this->assertFalse($instance->passes($this->context(['enum' => SuiteEnum::Hearts->value])));
        $this->assertFalse($instance->passes($this->context(['enum' => SuiteEnum::Spades->value])));
    }

    public function testWhenPassesForActualEnumValue()
    {
        $instance = when($this->enum, function (SuiteEnum $value) {
            return $value === SuiteEnum::Clubs;
        });

        $this->assertTrue($instance->passes($this->context(['enum' => SuiteEnum::Clubs])));
        $this->assertFalse($instance->passes($this->context(['enum' => SuiteEnum::Diamonds])));
        $this->assertFalse($instance->passes($this->context(['enum' => SuiteEnum::Hearts])));
        $this->assertFalse($instance->passes($this->context(['enum' => SuiteEnum::Spades])));
    }

    public function testAny()
    {
        $context = $this->context([]);

        $this->assertFalse(any([])->passes($context));
        $this->assertFalse(any([$this->boolPredicate(false)])->passes($context));
        $this->assertTrue(any([$this->boolPredicate(true)])->passes($context));
        $this->assertTrue(any([$this->boolPredicate(false), $this->boolPredicate(true), $this->boolPredicate(false)])->passes($context));
    }

    public function testAll()
    {
        $context = $this->context([]);

        $this->assertTrue(all([])->passes($context));
        $this->assertTrue(all([$this->boolPredicate(true)])->passes($context));
        $this->assertTrue(all([$this->boolPredicate(true), $this->boolPredicate(true)])->passes($context));
        $this->assertFalse(all([$this->boolPredicate(false)])->passes($context));
        $this->assertFalse(all([$this->boolPredicate(false), $this->boolPredicate(true)])->passes($context));
    }

    public function testNone()
    {
        $context = $this->context([]);

        $this->assertTrue(none([])->passes($context));
        $this->assertTrue(none([$this->boolPredicate(false)])->passes($context));
        $this->assertTrue(none([$this->boolPredicate(false), $this->boolPredicate(false)])->passes($context));

        $this->assertFalse(none([$this->boolPredicate(true)])->passes($context));
        $this->assertFalse(none([$this->boolPredicate(false), $this->boolPredicate(true)])->passes($context));
        $this->assertFalse(none([$this->boolPredicate(true), $this->boolPredicate(false)])->passes($context));
    }

    private function boolPredicate(bool $passes): Predicate
    {
        return new class(value: $passes) implements Predicate {

            public function __construct(private bool $value)
            {
                //
            }

            public function description(ResourceContext $context): string
            {
                return '';
            }

            public function passes(ResourceContext $context): bool
            {
                return $this->value;
            }

        };
    }
}