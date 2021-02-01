<?php


namespace Seier\Resting\Tests\Meta;


use Seier\Resting\Resource;
use Seier\Resting\Fields\StringField;
use Seier\Resting\Fields\ResourceArrayField;

class UnionListParentResource extends Resource
{
    
    public StringField $other;
    public ResourceArrayField $union;

    public function __construct()
    {
        $this->other = new StringField;
        $this->union = new ResourceArrayField(fn() => new UnionResourceBase());
    }
}