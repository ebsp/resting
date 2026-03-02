<?php


namespace Seier\Resting\Tests\Meta;


use Seier\Resting\Resource;
use Seier\Resting\Fields\IntField;
use Seier\Resting\Fields\StringField;

class NotRequiredPersonResource extends Resource
{
    public StringField $name;
    public IntField $age;

    public function __construct()
    {
        $this->name = (new StringField)->notRequired();
        $this->age = (new IntField)->notRequired();
    }
}