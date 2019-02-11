<?php

namespace Seier\Resting\Tests\Fields;

use PHPUnit\Framework\TestCase;
use Seier\Resting\Fields\IntField;

class IntTest extends TestCase
{
    public function testValidation()
    {
        $field = new IntField;
        $this->assertEquals($field->validation()[0], 'int');
    }

    public function testCasting()
    {
        $field = new IntField;
        $field->set('ok');
        $this->assertTrue(is_int($field->get()));
        $this->assertFalse(is_string($field->get()));
        $this->assertEquals(0, $field->get());
    }

    public function testEmptyReturnsNull()
    {
        $field = new IntField;
        $this->assertNull($field->get());
    }

    public function testNonNullable()
    {
        $field = (new IntField)->nullable(false);
        $this->assertEquals($field->get(), 0);
    }
}
