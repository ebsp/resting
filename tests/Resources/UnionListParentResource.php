<?php


namespace Seier\Resting\Tests\Resources;


use Seier\Resting\Fields\ResourceArrayField;
use Seier\Resting\Fields\StringField;
use Seier\Resting\Resource;

class UnionListParentResource extends Resource
{
    public $other;
    public $union;

    public function __construct()
    {
        $this->other = new StringField;
        $this->union = new ResourceArrayField(new UnionResourceBase());
    }
}