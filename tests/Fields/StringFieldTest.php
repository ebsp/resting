<?php

namespace Seier\Resting\Tests\Fields;

use Seier\Resting\Tests\TestCase;
use Seier\Resting\Fields\StringField;
use Seier\Resting\Exceptions\InvalidTypeException;

class StringFieldTest extends TestCase
{
    public function testValidation()
    {
        $field = new StringField;
        $this->assertEquals($field->validation()[0], 'string');
    }

    public function testInvalidTypeInt()
    {
        $this->expectException(InvalidTypeException::class);

        $field = new StringField;
        $field->set(1);
    }

    public function testInvalidTypeArray()
    {
        $this->expectException(InvalidTypeException::class);

        $field = new StringField;
        $field->set([]);
    }

    public function testInvalidTypeBool()
    {
        $this->expectException(InvalidTypeException::class);

        $field = new StringField;
        $field->set(false);
    }

    public function testEmptyReturnsNull()
    {
        $field = new StringField;
        $this->assertNull($field->get());
    }

    public function testNullable()
    {
        $field = new StringField;
        $field->set(null);
        $this->assertNull($field->get());
    }

    public function testNonNullable()
    {
        $field = (new StringField)->nullable(false);
        $this->assertEquals($field->get(), '');
    }
}
