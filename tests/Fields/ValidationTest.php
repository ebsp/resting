<?php

namespace Seier\Resting\Tests\Fields;

use PHPUnit\Framework\TestCase;
use Seier\Resting\Fields\StringField;

class ValidationTest extends TestCase
{
    public function testAdditionalValidation()
    {
        $field = new StringField;
        $this->assertEquals($field->validation()[0], 'string');
        $field->addValidation(['min:5']);
        $this->assertEquals('min:5', $field->validation()[2]);
    }
}
