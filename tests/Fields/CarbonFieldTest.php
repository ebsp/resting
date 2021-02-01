<?php

namespace Seier\Resting\Tests\Fields;

use Seier\Resting\Tests\TestCase;
use Seier\Resting\Fields\CarbonField;
use Seier\Resting\Parsing\CarbonParseError;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Tests\Meta\MockSecondaryValidator;
use Seier\Resting\Tests\Meta\MockSecondaryValidationError;
use Seier\Resting\Validation\Errors\NullableValidationError;
use Seier\Resting\Validation\Errors\NotCarbonValidationError;
use Seier\Resting\Validation\Secondary\Comparable\MinValidationError;
use Seier\Resting\Validation\Secondary\Carbon\CarbonMinValidationError;

class CarbonFieldTest extends TestCase
{

    use AssertsErrors;

    private CarbonField $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new CarbonField();
    }

    public function testGetCanReturnNull()
    {
        $this->assertNull($this->instance->get());
    }

    public function getGetCanReturnCarbonInstance()
    {
        $this->instance->set($now = now());
        $this->assertSame($now, $this->instance->get());
    }

    public function testSetCarbon()
    {
        $now = now();
        $this->instance->set($now);
        $this->assertSame($now, $this->instance->get());
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
            $this->instance->set(1);
        });

        $this->assertHasError($exception, NotCarbonValidationError::class);
    }

    public function testSetThrowsWhenCarbonFailsValidation()
    {
        $this->instance->min($limit = now());
        $validationException = $this->assertThrowsValidationException(function () use ($limit) {
            $this->instance->set($limit->copy()->subSecond());
        });

        $this->assertCount(1, $validationException->getErrors());
        $this->assertHasError($validationException, MinValidationError::class);
    }

    public function testSetDoesNotThrowWhenCarbonPassesValidation()
    {
        $this->instance->min($limit = now());
        $this->assertDoesNotThrowValidationException(function () use ($limit) {
            $this->instance->set($limit->copy()->addSecond());
        });
    }

    public function testSetCanParseDateString()
    {
        $now = now()->startOfDay();

        $this->instance->set($now->toDateString());
        $this->assertEquals($now, $this->instance->get());
    }

    public function testSetCanParseDatetimeString()
    {
        $now = now()->startOfSecond();

        $this->instance->set($now->toDateTimeString());
        $this->assertEquals($now, $this->instance->get());
    }

    public function testSetWhenProvidedNonParsableString()
    {
        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set('invalid');
        });

        $this->assertHasError($exception, CarbonParseError::class);
    }

    public function testFormattedWithoutCustomFormat()
    {
        $this->instance->set($now = now());

        $this->assertEquals(
            $now->toDateTimeString(),
            $this->instance->formatted(),
        );
    }

    public function testFormattedWithCustomFormat()
    {
        $this->instance->set($now = now());
        $this->instance->getFormatter()->withFormat($format = 'Y-m-d');

        $this->assertEquals(
            $now->format($format),
            $this->instance->formatted(),
        );
    }

    public function testValidateWithRegisteredSecondaryValidationThatPasses()
    {
        $this->instance->withValidator(MockSecondaryValidator::pass());

        $this->instance->set($now = now());
        $this->assertEquals($now, $this->instance->get());
    }

    public function testValidateWithRegisteredSecondaryValidationThatFails()
    {
        $this->instance->withValidator(MockSecondaryValidator::fail());

        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set(now());
        });

        $this->assertHasError($exception, MockSecondaryValidationError::class);
    }

    public function testCanCastEmptyValuesToNull()
    {
        $this->instance->emptyStringAsNull();

        $this->instance->set('');
        $this->assertNull($this->instance->get());
    }
}
