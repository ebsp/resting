<?php

namespace Seier\Resting\Tests\Meta;

use Seier\Resting\Params;
use Seier\Resting\Fields\IntField;
use Seier\Resting\Fields\StringField;

class PersonParams extends Params
{
    public StringField $name;
    public IntField $age;

    public function __construct()
    {
        $this->name = (new StringField)->notRequired();
        $this->age = (new IntField)->notRequired();
    }
}