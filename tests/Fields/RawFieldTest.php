<?php

namespace Fields;

use stdClass;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\Fields\RawField;
use Seier\Resting\Tests\Meta\AssertsErrors;
use Seier\Resting\Validation\Errors\NullableValidationError;

class RawFieldTest extends TestCase
{
    use AssertsErrors;

    private RawField $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new RawField;
    }

    public function testWithDifferentValueTypes()
    {
        $values = [
            1,
            5.5,
            $this->faker->uuid,
            [],
            new stdClass
        ];

        $this->assertFalse($this->instance->isFilled());
        foreach ($values as $value) {
            $this->instance->set($value);
            $this->assertTrue($this->instance->isFilled());
            $this->assertSame($value, $this->instance->get());
        }
    }

    public function testWhenNullableTrue()
    {
        $this->instance->nullable();

        $this->instance->set(null);

        $this->assertTrue($this->instance->isFilled());
        $this->assertTrue($this->instance->isNull());
        $this->assertNull($this->instance->get());
    }

    public function testWhenNullableFalse()
    {
        $this->instance->nullable(false);

        $validationException = $this->assertThrowsValidationException(function () {
            $this->instance->set(null);
        });

        $this->assertCount(1, $validationException->getErrors());
        $this->assertHasError($validationException, NullableValidationError::class);
    }
}