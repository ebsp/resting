<?php

namespace Seier\Resting\Tests\Fields;

use PHPUnit\Framework\TestCase;
use Seier\Resting\Fields\StringField;

class StringTest extends TestCase
{
    public function testValidation()
    {
        $field = new StringField;
        $this->assertEquals($field->validation()[0], 'string');
    }

    public function testCasting()
    {
        $field = new StringField;
        $field->set(1);
        $this->assertFalse(is_int($field->get()));
        $this->assertTrue(is_string($field->get()));
    }

    public function testEmptyReturnsNull()
    {
        $field = new StringField;
        $this->assertNull($field->get());
    }

    public function testNonNullable()
    {
        $field = (new StringField)->nullable(false);
        $this->assertEquals($field->get(), '');
    }
}
