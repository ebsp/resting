<?php

namespace Seier\Resting\Tests\Fields;

use Seier\Resting\Exceptions\InvalidTypeException;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\Fields\IntArrayField;
use Seier\Resting\Exceptions\NotArrayException;

class IntArrayFieldTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(IntArrayField::class, new IntArrayField);
    }

    public function testValueIsValidatedWhenSet()
    {
        $field = new IntArrayField;
        $this->expectException(NotArrayException::class);
        $field->set('john');
    }

    public function testValueTypeIsValidated()
    {
        $field = new IntArrayField;
        $this->expectException(InvalidTypeException::class);
        $field->set(['john']);
    }

    public function testPushValueTypeIsValidated()
    {
        $field = new IntArrayField;
        $this->expectException(InvalidTypeException::class);
        $field->push('john');
    }

    public function testSetValue()
    {
        $field = new IntArrayField;
        $field->set([8]);
        $this->assertEquals([8], $field->get());
        $field->set([9]);
        $this->assertEquals([9], $field->get());
    }

    public function testPushValue()
    {
        $field = new IntArrayField;
        $field->push(2);
        $this->assertEquals([2], $field->get());
        $field->push(4);
        $this->assertEquals([2, 4], $field->get());
    }
}
