<?php

namespace Seier\Resting\Tests\Fields;

use PHPUnit\Framework\TestCase;
use Seier\Resting\Fields\EnumField;
use Seier\Resting\Exceptions\InvalidEnumException;
use Seier\Resting\Exceptions\InvalidEnumOptionsException;

class EnumTest extends TestCase
{
    public function testValueIsValidatedWhenSet()
    {
        $field = new EnumField('john', 'doe');
        $this->expectException(InvalidEnumException::class);
        $field->set('luke');
    }

    public function testValueCanBeSet()
    {
        $field = new EnumField('john', 'doe');
        $field->set('john');
        $this->assertEquals('john', $field->get());
    }

    public function testEnumValidation()
    {
        $field = new EnumField('john', 'doe');
        $validation = $field->validation();

        $this->assertEquals(2, count($validation));
        $nullableKey = array_search('nullable', $validation);
        $this->assertTrue($nullableKey !== false);

        unset($validation[$nullableKey]);

        $enumRule = $validation[0];
        $this->assertTrue(str_contains($enumRule, 'in:'));
        $this->assertTrue(str_contains($enumRule, 'john'));
        $this->assertTrue(str_contains($enumRule, 'doe'));
    }

    public function testArrayAsOptions()
    {
        $field = new EnumField(['john', 'doe']);
        $this->assertTrue(in_array('john', $field->options()));
        $this->assertTrue(in_array('doe', $field->options()));
        $this->assertTrue($field->isValid('john'));
        $this->assertTrue($field->isValid('doe'));
    }

    public function testExceptionIsThrownWhenArrayAndVariousOptionsAreGiven()
    {
        $this->expectException(InvalidEnumOptionsException::class);
        new EnumField(['john'], 'doe');
    }
}
