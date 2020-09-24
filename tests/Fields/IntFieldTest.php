<?php

namespace Seier\Resting\Tests\Fields;

use Codeception\AssertThrows;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\Fields\IntField;

class IntFieldTest extends TestCase
{
    use AssertThrows;

    public function testValidation()
    {
        $field = new IntField;
        $this->assertEquals($field->validation()[0], 'int');
    }

    public function testIntFieldValidationWhenString()
    {
        $this->assertThrowsWithMessage(\Exception::class, 'validation.int', function () {
            $field = new IntField;
            $field->set('ok');
        });
    }

    public function testIntFieldCanCastNumericStrings()
    {
        $field = new IntField;
        $field->set('1');
        $this->assertEquals(1, $field->get());
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
