<?php

namespace Seier\Resting\Tests\Fields;

use Seier\Resting\Tests\TestCase;
use Seier\Resting\Fields\StringField;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Exceptions\ValidationException;
use Seier\Resting\Tests\Meta\MockSecondaryValidator;
use Seier\Resting\Tests\Meta\MockSecondaryValidationError;
use Seier\Resting\Validation\Secondary\String\StringMaxLengthValidationError;
use Seier\Resting\Validation\Secondary\String\StringMinLengthValidationError;

class StringFieldTest extends TestCase
{

    use AssertsErrors;

    private StringField $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new StringField();
    }

    public function testGetEmptyReturnsNull()
    {
        $this->assertNull($this->instance->get());
    }

    public function testGetNotEmptyReturnsNullWhenEmpty()
    {
        $this->instance->set('');

        $this->assertNull($this->instance->getNotEmpty());
    }

    public function testGetNotEmptyReturnsValueWhenNotEmpty()
    {
        $this->instance->trim(false);
        $this->instance->set(' ');

        $this->assertEquals(' ', $this->instance->getNotEmpty());
    }

    public function testGetNotEmptyReturnsValueWhenTrimming()
    {
        $this->instance->set(' abc ');

        $this->assertEquals('abc', $this->instance->getNotEmpty(trim: true));
    }

    public function testGetNotEmptyReturnsNullWhenTrimming()
    {
        $this->instance->set("\t\r ");

        $this->assertNull($this->instance->getNotEmpty(trim: true));
    }

    public function testIsNullReturnsTrue()
    {
        $this->assertTrue($this->instance->isNull());
    }

    public function testSetWhenGivenString()
    {
        $this->instance->set($expected = $this->faker->word);

        $this->assertEquals($expected, $this->instance->get());
    }

    public function testSetWhenGivenWrongType()
    {
        $this->assertThrows(ValidationException::class, function () {
            $this->instance->set(1);
        });
    }

    public function testNullableSetWhenGivenNull()
    {
        $this->instance->nullable();

        $this->instance->set(null);
        $this->assertNull($this->instance->get());
    }

    public function testNonNullableSetWhenGivenNull()
    {
        $this->instance->nullable(false);

        $this->assertThrows(ValidationException::class, function () {
            $this->instance->set(null);
        });
    }

    public function testValidateWithRegisteredSecondaryValidationThatPasses()
    {
        $this->instance->withValidator(MockSecondaryValidator::pass());

        $this->instance->set($expected = '');
        $this->assertEquals($expected, $this->instance->get());
    }

    public function testValidateWithRegisteredSecondaryValidationThatFails()
    {
        $this->instance->withValidator(MockSecondaryValidator::fail());

        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set('');
        });

        $this->assertHasError($exception, MockSecondaryValidationError::class);
    }

    public function testCanCastEmptyValuesToNull()
    {
        $this->instance->nullable();
        $this->instance->emptyStringAsNull();

        $this->instance->set('');
        $this->assertNull($this->instance->get());
    }

    public function testTransformersCanChangeSetValue()
    {
        $this->instance->transform(strtoupper(...));
        $this->instance->transform(trim(...));

        $this->instance->set('a ');

        $this->assertSame('A', $this->instance->get());
    }

    public function testTrimsByDefault()
    {
        $this->instance->set(" \t a \n ");

        $this->assertSame('a', $this->instance->get());
        $this->assertTrue($this->instance->isTrimmed());
    }

    public function testTrimCanBeDisabled()
    {
        $this->instance->trim(false);

        $this->instance->set($expected = ' a ');

        $this->assertSame($expected, $this->instance->get());
        $this->assertFalse($this->instance->isTrimmed());
    }

    public function testTrimCanBeReEnabled()
    {
        $this->instance->trim(false);
        $this->instance->trim();

        $this->instance->set(' a ');

        $this->assertSame('a', $this->instance->get());
    }

    public function testMinLengthValidatesTrimmedValue()
    {
        $this->instance->minLength(2);

        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set(' a ');
        });

        $this->assertHasError($exception, StringMinLengthValidationError::class);
    }

    public function testMinLengthValidatesUntrimmedValueWhenTrimIsDisabled()
    {
        $this->instance->trim(false);
        $this->instance->minLength(2);

        $this->assertDoesNotThrowValidationException(function () {
            $this->instance->set(' a ');
        });
    }

    public function testMaxLengthValidatesTrimmedValue()
    {
        $this->instance->maxLength(1);

        $this->assertDoesNotThrowValidationException(function () {
            $this->instance->set(' a ');
        });

        $this->assertSame('a', $this->instance->get());
    }

    public function testMaxLengthValidatesUntrimmedValueWhenTrimIsDisabled()
    {
        $this->instance->trim(false);
        $this->instance->maxLength(1);

        $exception = $this->assertThrowsValidationException(function () {
            $this->instance->set(' a ');
        });

        $this->assertHasError($exception, StringMaxLengthValidationError::class);
    }

    public function testLowerTransformer()
    {
        $this->instance->lower();

        $this->instance->set('ÆØÅ');

        $this->assertSame('æøå', $this->instance->get());
    }

    public function testUpperTransformer()
    {
        $this->instance->upper();

        $this->instance->set('æøå');

        $this->assertSame('ÆØÅ', $this->instance->get());
    }

    public function testEmptyStringAsNull()
    {
        $this->instance->nullable();
        $this->instance->emptyStringAsNull();

        $this->instance->set('');

        $this->assertNull($this->instance->get());
    }

    public function testTrimWithEmptyStringAsNull()
    {
        $this->instance->nullable();
        $this->instance->emptyStringAsNull();
        $this->instance->trim();

        $this->instance->set(' ');

        $this->assertNull($this->instance->get());
    }

    public function testTrimAndRemoveEmptyWithNonNullValidation()
    {
        $this->instance->nullable(false);
        $this->instance->emptyStringAsNull();
        $this->instance->trim();

        $this->assertThrows(ValidationException::class, function () {
            $this->instance->set(' ');
        });

        $this->assertNull($this->instance->get());
    }

    public function testTrimAndRemoveEmptyWithMinLengthValidation()
    {
        $this->instance->nullable(false);
        $this->instance->minLength(1);
        $this->instance->trim();

        $this->assertThrows(ValidationException::class, function () {
            $this->instance->set('  ');
        });

        $this->assertNull($this->instance->get());
    }

    public function testStripWritespace()
    {
        $this->instance->stripWhitespace();

        $this->instance->set(' ');
        $this->assertSame('', $this->instance->get());

        $this->instance->set("\ta\n");
        $this->assertSame('a', $this->instance->get());
        
        $this->instance->set("\tå\n");
        $this->assertSame('å', $this->instance->get());

        $this->instance->set("\t生\n");
        $this->assertSame('生', $this->instance->get());
    }
}
