<?php


namespace Seier\Resting\Tests\Meta;


use Seier\Resting\Resource;
use Seier\Resting\Fields\StringField;
use Seier\Resting\Fields\CarbonField;

class EventResource extends Resource
{

    public StringField $name;
    public CarbonField $time;

    public function __construct()
    {
        $this->name = new StringField();
        $this->time = new CarbonField();
    }
}