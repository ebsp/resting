<?php


namespace Seier\Resting\Tests\Resources;


use Seier\Resting\Fields\StringField;
use Seier\Resting\Resource;

class UnionResourceA extends Resource
{

    public $value;
    public $a_specific;
    public $discriminator;

    public function __construct()
    {
        $this->value = new StringField;
        $this->a_specific = new StringField;
        $this->discriminator = new StringField;
    }
}