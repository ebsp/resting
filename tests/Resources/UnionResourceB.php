<?php


namespace Seier\Resting\Tests\Resources;


use Seier\Resting\Fields\StringField;
use Seier\Resting\Resource;

class UnionResourceB extends Resource
{

    public $value;
    public $b_specific;

    public function __construct()
    {
        $this->value = (new StringField)->required();
        $this->b_specific = new StringField;
    }
}