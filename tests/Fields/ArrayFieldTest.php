<?php

namespace Seier\Resting\Tests\Fields;

use Seier\Resting\Tests\TestCase;
use Seier\Resting\Fields\ArrayField;
use Seier\Resting\Validation\IntValidator;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Validation\ArrayValidator;
use Seier\Resting\Validation\StringValidator;
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

    public function testGetCanNotReturnNull()
    {
        $this->assertNotNull($this->instance->get());
    }

    public function testEmptyGetReturnArray()
    {
        $this->assertIsArray($this->instance->get());
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
}
