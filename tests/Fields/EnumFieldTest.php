<?php

namespace Seier\Resting\Tests\Fields;

use stdClass;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\Fields\EnumField;
use Seier\Resting\Tests\Meta\SuiteEnum;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Validation\Errors\InValidation;
use Seier\Resting\Exceptions\ValidationException;
use Seier\Resting\Validation\Errors\EnumValidationError;
use Seier\Resting\Validation\Secondary\In\InValidationError;

class EnumFieldTest extends TestCase
{
    use AssertsErrors;

    private EnumField $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new EnumField(SuiteEnum::class);
    }

    public function testWhenProvidedValidValue()
    {
        foreach (SuiteEnum::cases() as $case) {
            $this->instance->set($case);
            $this->assertSame($case, $this->instance->get());
        }
    }

    public function testWhenProvidedValidBackingValue()
    {
        foreach (SuiteEnum::cases() as $case) {
            $this->instance->set($case->value);
            $this->assertSame($case, $this->instance->get());
        }
    }

    public function testWhenNullableAndProvidedNull()
    {
        $this->instance->nullable();

        $this->instance->set(null);

        $this->assertNull($this->instance->get());
    }

    public function testWhenNotNullableAndProvidedNull()
    {
        $this->instance->nullable(false);

        $this->instance->set(SuiteEnum::Clubs);

        $this->assertThrowsValidationException(function () {
            $this->instance->set(null);
        });

        $this->assertSame(SuiteEnum::Clubs, $this->instance->get());
    }

    public function testWhenGivenInvalidStringBackingValue()
    {
        $wrongValue = $this->faker->uuid();
        $exception = $this->assertThrowsValidationException(fn() => $this->instance->set($wrongValue));

        $this->assertCount(1, $exception->getErrors());
        $this->assertInstanceOf(EnumValidationError::class, $error = $exception->getErrors()[0]);

        $this->assertStringContainsString($wrongValue, $error->getMessage());
        foreach (SuiteEnum::cases() as $case) {
            $this->assertStringContainsString($case->value, $error->getMessage());
        }
    }

    public function testWhenGivenInvalidNonStringValue()
    {
        $wrongValue = new stdClass;
        $exception = $this->assertThrowsValidationException(fn() => $this->instance->set($wrongValue));

        $this->assertCount(1, $exception->getErrors());
        $this->assertInstanceOf(EnumValidationError::class, $error = $exception->getErrors()[0]);

        $this->assertStringContainsString(get_class($wrongValue), $error->getMessage());
        $this->assertStringContainsString(SuiteEnum::class, $error->getMessage());
        foreach (SuiteEnum::cases() as $case) {
            $this->assertStringContainsString($case->name, $error->getMessage());
        }
    }

    public function testWhenUsingInValidationThatPasses()
    {
        foreach (SuiteEnum::cases() as $case) {
            $this->instance->in([$case]);
            $this->instance->set($case);
            $this->assertSame($case, $this->instance->get());
        }
    }

    public function testWhenUsingInValidationThatFails()
    {
        foreach (SuiteEnum::cases() as $case) {
            $this->instance->in([]);
            $exception = $this->assertThrowsValidationException(fn() => $this->instance->set($case));
            $this->assertInstanceOf(ValidationException::class, $exception);
            $this->assertCount(1, $exception->getErrors());
            $this->assertInstanceOf(InValidationError::class, $exception->getErrors()[0]);
            $this->assertNull($this->instance->get());
        }
    }
}
