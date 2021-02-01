<?php


namespace Seier\Resting\Tests\Meta;


use Seier\Resting\Resource;
use Seier\Resting\Fields\StringField;
use Seier\Resting\Fields\ResourceField;

class UnionParentResource extends Resource
{

    public StringField $other;
    public ResourceField $union;

    public function __construct()
    {
        $this->other = new StringField;
        $this->union = new ResourceField(fn() => new UnionResourceBase());
    }
}