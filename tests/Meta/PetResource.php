<?php


namespace Seier\Resting\Tests\Meta;


use Seier\Resting\Resource;
use Seier\Resting\Fields\StringField;
use Seier\Resting\Fields\ResourceField;

class PetResource extends Resource
{

    public StringField $name;
    public ResourceField $owner;

    public function __construct()
    {
        $this->name = new StringField();
        $this->owner = new ResourceField(fn() => new PersonResource);
    }

    public function getOwner(): ?PersonResource
    {
        return $this->owner->get();
    }
}