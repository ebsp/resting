<?php

namespace Seier\Resting\Tests\Fields;

use Seier\Resting\Tests\TestCase;
use Seier\Resting\Fields\ArrayField;
use Seier\Resting\Parsing\BoolParser;
use Seier\Resting\Tests\Meta\SuiteEnum;
use Seier\Resting\Tests\Support\TestEnum;
use Seier\Resting\Validation\IntValidator;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Tests\Meta\SuiteResource;
use Seier\Resting\Validation\BoolValidator;
use Seier\Resting\Tests\Meta\MockSecondaryValidator;
use Seier\Resting\Validation\Errors\NotIntValidationError;
use Seier\Resting\Tests\Meta\MockSecondaryValidationError;
use Seier\Resting\Validation\Errors\NotArrayValidationError;
use Seier\Resting\Validation\Errors\NullableValidationError;
use Seier\Resting\Validation\Secondary\Arrays\ArraySizeValidationError;

class ArrayFieldTest extends TestCase
{

    use AssertsErrors;

    private ArrayField $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new ArrayField();
    }

    public function testGetCanReturnNull()
    {
        $this->assertNull($this->instance->get());
    }

    public function getGetCanReturnArray()
    {
        $this->instance->set([]);
        $this->assertEquals([], $this->instance->get());
    }

    public function testSetArray()
    {
        $expected = [1, 2, 3];

        $this->instance->set($expected);
        $this->assertEquals($expected, $this->instance->get());
    }

    public function testSetArrayEmpty()
    {
        $expected = [];

        $this->instance->set($expected);
        $this->assertEquals($expected, $this->instance->get());
    }

    public function testSetCollection()
    {
        $expected = collect([1, 2, 3]);

        $this->instance->set($expected);
        $this->assertEquals($expected->toArray(), $this->instance->get());
    }

    public function testSetCollectionEmpty()
    {
        $expected = collect();

        $this->instance->set($expected);
        $this->assertEquals($expected->toArray(), $this->instance->get());
    }

    public function testSetNullWhenNullable()
    {
        $this->instance->nullable();
        $this->instance->set(null);
        $this->assertNull($this->instance->get());
    }

    public function testSetNullWhenNotNullable()
    {
        $this->instance->notNullable();

        $validationException = $this->assertThrowsValidationException(function () {
            $this->instance->set(null);
        });

        $this->assertHasError($validationException, NullableValidationError::class);
    }

    public function testSetThrowsWhenProvidedWrongType()
    {
        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set('');
        });

        $this->assertHasError($exception, NotArrayValidationError::class);
    }

    public function testSetThrowsWhenArrayFailsValidation()
    {
        $this->instance->size(1);

        $validationException = $this->assertThrowsValidationException(function () {
            $this->instance->set([1, 2]);
        });

        $this->assertCount(1, $validationException->getErrors());
        $this->assertHasError($validationException, ArraySizeValidationError::class);
    }

    public function testSetThrowsWhenElementFailsValidation()
    {
        $this->instance->setElementValidator(new IntValidator());
        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set(['a', 'b', 1]);
        });

        $this->assertCount(2, $exception->getErrors());
        $this->assertHasError($exception, NotIntValidationError::class, 0);
        $this->assertHasError($exception, NotIntValidationError::class, 1);
    }

    public function testValidateWithRegisteredSecondaryValidationThatPasses()
    {
        $this->instance->withValidator(MockSecondaryValidator::pass());

        $this->instance->set([]);
        $this->assertEquals([], $this->instance->get());
    }

    public function testValidateWithRegisteredSecondaryValidationThatFails()
    {
        $this->instance->withValidator(MockSecondaryValidator::fail());

        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set([]);
        });

        $this->assertHasError($exception, MockSecondaryValidationError::class);
    }

    public function testArrayFieldOfEnums()
    {
        $field = new ArrayField();
        $field->ofEnums(SuiteEnum::class);

        $field->set([SuiteEnum::Spades, SuiteEnum::Clubs, SuiteEnum::Diamonds]);
        $this->assertSame(
            [SuiteEnum::Spades, SuiteEnum::Clubs, SuiteEnum::Diamonds],
            $field->get()
        );

        $field->set([SuiteEnum::Spades, SuiteEnum::Diamonds]);
        $this->assertSame(
            [SuiteEnum::Spades, SuiteEnum::Diamonds],
            $field->get()
        );

        $field->set([SuiteEnum::Diamonds]);
        $this->assertSame(
            [SuiteEnum::Diamonds],
            $field->get()
        );
    }

    public function testArrayFieldOfEnumsSerializesEnumBackedValues()
    {
        $resource = new SuiteResource();

        $resource->suites->set([SuiteEnum::Spades, SuiteEnum::Clubs, SuiteEnum::Diamonds]);
        $this->assertSame(
            ['suites' => [SuiteEnum::Spades->value, SuiteEnum::Clubs->value, SuiteEnum::Diamonds->value]],
            $resource->toResponseArray()
        );

        $resource->suites->set([SuiteEnum::Spades, SuiteEnum::Diamonds]);
        $this->assertSame(
            ['suites' => [SuiteEnum::Spades->value, SuiteEnum::Diamonds->value]],
            $resource->toResponseArray()
        );

        $resource->suites->set([SuiteEnum::Diamonds]);
        $this->assertSame(
            ['suites' => [SuiteEnum::Diamonds->value]],
            $resource->toResponseArray()
        );
    }

    public function testDoesNotAllowNullElementsByDefault()
    {
        $this->instance->ofIntegers();

        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set([1, 2, 3, null]);
        });

        $this->assertHasError($exception, NullableValidationError::class, path: '3');
        $this->assertNull($this->instance->get());
        $this->assertFalse($this->instance->allowsNullElements());
    }

    public function testCanAllowNullElements()
    {
        $this->instance->ofIntegers();
        $this->instance->allowNullElements();

        $this->instance->set([1, 2, 3, null]);

        $this->assertSame(
            [1, 2, 3, null],
            $this->instance->get()
        );

        $this->assertTrue($this->instance->allowsNullElements());
    }

    public function testAllowNullsCanDisallowNullAfterBeingAllowed()
    {
        $this->instance->ofIntegers();
        $this->instance->allowNullElements(true);
        $this->instance->allowNullElements(false);

        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set([1, 2, 3, null]);
        });

        $this->assertHasError($exception, NullableValidationError::class, path: '3');
        $this->assertNull($this->instance->get());
        $this->assertFalse($this->instance->allowsNullElements());
    }

    public function testOfMethodsCanSetNullability()
    {
        $this->instance->ofIntegers(nullable: true);
        $this->assertTrue($this->instance->allowsNullElements());

        $this->instance->ofStrings(nullable: false);
        $this->assertFalse($this->instance->allowsNullElements());

        $this->instance->ofNumbers(nullable: true);
        $this->assertTrue($this->instance->allowsNullElements());

        $this->instance->ofBooleans(nullable: false);
        $this->assertFalse($this->instance->allowsNullElements());

        $this->instance->ofTimes(nullable: true);
        $this->assertTrue($this->instance->allowsNullElements());

        $this->instance->ofArrays(nullable: false);
        $this->assertFalse($this->instance->allowsNullElements());

        $this->instance->ofCarbons(nullable: true);
        $this->assertTrue($this->instance->allowsNullElements());

        $this->instance->ofEnums(SuiteEnum::class, nullable: false);
        $this->assertFalse($this->instance->allowsNullElements());

        $this->instance->of(validator: new BoolValidator(), parser: new BoolParser(), nullable: true);
        $this->assertTrue($this->instance->allowsNullElements());
    }
}
