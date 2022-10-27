<?php


namespace Seier\Resting\Tests\Fields;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Fields\ArrayField;
use Seier\Resting\Fields\StringField;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Validation\Errors\NullableValidationError;
use Seier\Resting\Validation\Errors\NotStringValidationError;
use Seier\Resting\Validation\Secondary\Anonymous\AnonymousValidationError;

class FieldTest extends TestCase
{

    use AssertsErrors;

    private StringField $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new StringField();
    }

    public function testSetWhenDefaultNullable()
    {
        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set(null);
        });

        $this->assertHasError($exception, NullableValidationError::class);
    }

    public function testSetNullWhenNullable()
    {
        $this->instance->nullable(true);
        $this->instance->set(null);

        $this->assertNull($this->instance->get());
    }

    public function testSetNullWhenNotNullable()
    {
        $this->instance->nullable(false);

        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set(null);
        });

        $this->assertHasError($exception, NullableValidationError::class);
    }

    public function testWithDefaultMarksFieldNotRequired()
    {
        $this->assertTrue($this->instance->isRequired());
        $this->instance->withDefault($this->faker->word);
        $this->assertFalse($this->instance->isRequired());
    }

    public function testWithDefaultSetsValueIfNotYetSet()
    {
        $default = $this->faker->word;
        $this->instance->withDefault($default);
        $this->assertEquals($default, $this->instance->get());
    }

    public function testWithDefaultValidatesValue()
    {
        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->withDefault(1);
        });

        $this->assertHasError($exception, NotStringValidationError::class);
    }

    public function testIsNullInitial()
    {
        $this->assertTrue($this->instance->isNull());
    }

    public function testIsNullWhenNullIsSet()
    {
        $this->instance->nullable();
        $this->instance->set(null);
        $this->assertTrue($this->instance->isNull());
    }

    public function testIsNullWhenNullIsNotSet()
    {
        $this->instance->nullable();
        $this->instance->set('');
        $this->assertFalse($this->instance->isNull());
    }

    public function testIsNotNullInitial()
    {
        $this->assertFalse($this->instance->isNotNull());
    }

    public function testIsNotNullWhenNullIsSet()
    {
        $this->instance->nullable();
        $this->instance->set(null);
        $this->assertFalse($this->instance->isNotNull());
    }

    public function testIsNotNullWhenNullIsNotSet()
    {
        $this->instance->nullable();
        $this->instance->set('');
        $this->assertTrue($this->instance->isNotNull());
    }

    public function testIsEmptyInitial()
    {
        $this->assertTrue($this->instance->isEmpty());
    }

    public function testIsEmptyWhenNull()
    {
        $this->instance->nullable();
        $this->instance->set(null);
        $this->assertTrue($this->instance->isEmpty());
    }

    public function testIsEmptyWhenEmptyString()
    {
        $this->instance->set('');
        $this->assertTrue($this->instance->isEmpty());
    }

    public function testIsEmptyWhenEmptyArray()
    {
        $field = new ArrayField();
        $field->set([]);
        $this->assertTrue($this->instance->isEmpty());
    }

    public function testIsEmptyWhenNonEmptyString()
    {
        $this->instance->set('not empty');
        $this->assertFalse($this->instance->isEmpty());
    }

    public function testIsEmptyWhenNonEmptyArray()
    {
        $field = new ArrayField();
        $field->set([1]);
        $this->assertFalse($field->isEmpty());
    }

    public function testIsNotEmptyInitial()
    {
        $this->assertFalse($this->instance->isNotEmpty());
    }

    public function testIsNotEmptyWhenNull()
    {
        $this->instance->nullable();
        $this->instance->set(null);
        $this->assertFalse($this->instance->isNotEmpty());
    }

    public function testIsNotEmptyWhenEmptyString()
    {
        $this->instance->set('');
        $this->assertFalse($this->instance->isNotEmpty());
    }

    public function testIsNotEmptyWhenEmptyArray()
    {
        $field = new ArrayField();
        $field->set([]);
        $this->assertFalse($field->isNotEmpty());
    }

    public function testIsNotEmptyWhenNonEmptyString()
    {
        $this->instance->set('not empty');
        $this->assertTrue($this->instance->isNotEmpty());
    }

    public function testIsNotEmptyWhenNonEmptyArray()
    {
        $field = new ArrayField();
        $field->set([1]);
        $this->assertTrue($field->isNotEmpty());
    }

    public function testIsFilledDefault()
    {
        $this->assertFalse($this->instance->isFilled());
    }

    public function testIsFilledAfterSetNull()
    {
        $this->instance->nullable();
        $this->instance->set(null);
        $this->assertTrue($this->instance->isFilled());
    }

    public function testIsFilledAfterSetNonNullValue()
    {
        $this->instance->set('');
        $this->assertTrue($this->instance->isFilled());
    }

    public function testIsNotFilledDefault()
    {
        $this->assertTrue($this->instance->isNotFilled());
    }

    public function testIsNotFilledAfterSetNull()
    {
        $this->instance->nullable();
        $this->instance->set(null);
        $this->assertFalse($this->instance->isNotFilled());
    }

    public function testIsNotFilledAfterSetNonNullValue()
    {
        $this->instance->set('');
        $this->assertFalse($this->instance->isNotFilled());
    }

    public function testSetMarksFieldFilled()
    {
        $this->assertFalse($this->instance->isFilled());
        $this->instance->setFilled(true);
        $this->assertTrue($this->instance->isFilled());
        $this->instance->setFilled(false);
        $this->assertFalse($this->instance->isFilled());
    }

    public function testIsEnabledInitial()
    {
        $this->assertTrue($this->instance->isEnabled());
    }

    public function testEnableFalse()
    {
        $this->instance->enable(false);
        $this->assertFalse($this->instance->isEnabled());
    }

    public function testEnableDefault()
    {
        $this->instance->enable(false);
        $this->assertFalse($this->instance->isEnabled());
        $this->instance->enable();
        $this->assertTrue($this->instance->isEnabled());
    }

    public function testEnableTrue()
    {
        $this->instance->enable(false);
        $this->assertFalse($this->instance->isEnabled());
        $this->instance->enable(true);
        $this->assertTrue($this->instance->isEnabled());
    }

    public function testDisable()
    {
        $this->instance->disable();
        $this->assertFalse($this->instance->isEnabled());
    }

    public function testValidateThatWhenPasses()
    {
        $description = $this->faker->text;
        $this->instance->validateThat($description, fn(string $value) => $value === '');

        $this->instance->set('');
        $this->assertEquals('', $this->instance->get());
    }

    public function testValidateThatWhenFails()
    {
        $description = $this->faker->text;
        $this->instance->validateThat($description, fn(string $value) => $value === '');

        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set('a');
        });

        $this->assertHasError($exception, AnonymousValidationError::class);
    }
}