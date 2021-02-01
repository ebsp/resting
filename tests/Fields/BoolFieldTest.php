<?php


namespace Seier\Resting\Tests\Fields;


use Seier\Resting\Tests\TestCase;
use Seier\Resting\Fields\BoolField;
use Seier\Resting\Tests\Meta\MockSecondaryValidator;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Tests\Meta\MockSecondaryValidationError;
use Seier\Resting\Validation\Errors\NotBoolValidationError;
use Seier\Resting\Validation\Errors\NullableValidationError;

class BoolFieldTest extends TestCase
{

    use AssertsErrors;

    private BoolField $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new BoolField();
    }

    public function testGetCanReturnNull()
    {
        $this->assertNull($this->instance->get());
    }

    public function getGetCanReturnTrue()
    {
        $this->instance->set(true);
        $this->assertTrue($this->instance->get());
    }

    public function getGetCanReturnFalse()
    {
        $this->instance->set(false);
        $this->assertFalse($this->instance->get());
    }

    public function testSetTrue()
    {
        $this->instance->set(true);
        $this->assertTrue($this->instance->get());
    }

    public function testSetFalse()
    {
        $this->instance->set(false);
        $this->assertFalse($this->instance->get());
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

        $this->assertHasError($exception, NotBoolValidationError::class);
    }

    public function testValidateWithRegisteredSecondaryValidationThatPasses()
    {
        $this->instance->withValidator(MockSecondaryValidator::pass());

        $this->instance->set(true);
        $this->assertTrue($this->instance->get());
    }

    public function testValidateWithRegisteredSecondaryValidationThatFails()
    {
        $this->instance->withValidator(MockSecondaryValidator::fail());

        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set(true);
        });

        $this->assertHasError($exception, MockSecondaryValidationError::class);
    }
}