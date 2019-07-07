<?php

namespace Seier\Resting\Tests\Fields;

use Seier\Resting\Tests\TestCase;
use Seier\Resting\Fields\StringField;

class FieldValidationTest extends TestCase
{
    public function testAdditionalValidation()
    {
        $field = new StringField;
        $this->assertEquals($field->validation()[0], 'string');
        $field->addValidation(['min:5']);
        $this->assertEquals('min:5', $field->validation()[2]);
    }
}
