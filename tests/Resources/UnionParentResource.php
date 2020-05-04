<?php


namespace Seier\Resting\Tests\Resources;


use Seier\Resting\Fields\ResourceField;
use Seier\Resting\Fields\StringField;
use Seier\Resting\Resource;
use Seier\Resting\UnionResource;

class UnionParentResource extends Resource
{

    public $other;
    public $union;

    public function __construct()
    {
        $this->other = new StringField;
        $this->union = new ResourceField(new UnionResource('discriminator', [
            'a' => new UnionResourceA(),
            'b' => new UnionResourceB()
        ]));
    }
}