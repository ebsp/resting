<?php


namespace Seier\Resting\Tests\Meta;


use Seier\Resting\Resource;
use Seier\Resting\Fields\IntField;
use Seier\Resting\Fields\StringField;

class PersonResource extends Resource
{

    public StringField $name;
    public IntField $age;

    public function __construct()
    {
        $this->name = new StringField;
        $this->age = new IntField;
    }

    public static function nullableName(): static
    {
        $resource = new static();
        $resource->name->nullable();
        $resource->age->notRequired();

        return $resource;
    }

    public function from(Person $person): static
    {
        $this->name->set($person->name);
        $this->age->set($person->age);

        return $this;
    }
}