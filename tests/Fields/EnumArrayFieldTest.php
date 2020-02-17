<?php

namespace Seier\Resting\Tests\Fields;

use Illuminate\Support\Arr;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\Rules\EnumArrayRule;
use Seier\Resting\Fields\EnumArrayField;
use Seier\Resting\Exceptions\InvalidEnumException;
use Seier\Resting\Exceptions\InvalidEnumOptionsException;

class EnumArrayFieldTest extends TestCase
{
    public function testValuesCanBeSetFromArray()
    {
        $field = new EnumArrayField(['john', 'doe']);
        $this->assertTrue(in_array('john', $field->options()));
        $this->assertTrue(in_array('doe', $field->options()));
    }

    public function testValuesCanBeSetFromMultipleArguments()
    {
        $field = new EnumArrayField('john', 'doe');
        $this->assertTrue(in_array('john', $field->options()));
        $this->assertTrue(in_array('doe', $field->options()));
    }

    public function testValueIsValidatedWhenSet()
    {
        $field = new EnumArrayField('john', 'doe');
        $this->expectException(InvalidEnumException::class);
        $field->set(['luke']);
    }

    public function testValueTypeIsValidatedWhenSet()
    {
        $field = new EnumArrayField('john', 'doe');
        $this->expectException(InvalidEnumException::class);
        $field->set([false]);
    }

    public function testSetOption()
    {
        $field = new EnumArrayField('john', 'doe');
        $field->set(['john']);
        $this->assertEquals(['john'], $field->get());
    }


    public function testEnumValidation()
    {
        $field = new EnumArrayField(['john', 'doe']);
        $validation = $field->validation();
        $this->assertEquals(3, count($validation));
        $nullableKey = array_search('nullable', $validation);
        $this->assertNotFalse($nullableKey);
        unset($validation[$nullableKey]);

        $arrayKey = array_search('array', $validation);
        $this->assertNotFalse($arrayKey);
        unset($validation[$arrayKey]);

        $this->assertInstanceOf(EnumArrayRule::class, Arr::first($validation));
        $this->assertEquals($field->options(), Arr::first($validation)->options());
    }

    public function testArrayAsOptions()
    {
        $field = new EnumArrayField(['john', 'doe']);
        $this->assertTrue(in_array('john', $field->options()));
        $this->assertTrue(in_array('doe', $field->options()));
        $this->assertTrue($field->isValidOption('john'));
        $this->assertTrue($field->isValidOption('doe'));
    }

    public function testExceptionIsThrownWhenArrayAndVariousOptionsAreGiven()
    {
        $this->expectException(InvalidEnumOptionsException::class);
        new EnumArrayField(['john'], 'doe');
    }
}
