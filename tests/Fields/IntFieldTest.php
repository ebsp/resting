<?php

namespace Seier\Resting\Tests\Fields;

use Codeception\AssertThrows;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\Fields\IntField;
use Illuminate\Validation\Factory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;

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
        $field = new IntField;
        $field->set('ok');

        $filesystem = new Filesystem;
        $loader = new FileLoader($filesystem, dirname(dirname(__FILE__)) . '/lang');
        $translator = new Translator($loader, 'en');
        $val = new Factory($translator);
        $val = $val->make(['field' => 'a'], ['field' => $field->validation()]);
        $messages = $val->messages();

        $field = $messages->get('field');
        $this->assertNotNull($field);
        $this->assertContains('validation.integer', $field);
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
