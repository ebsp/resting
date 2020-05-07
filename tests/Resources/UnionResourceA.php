<?php


namespace Seier\Resting\Tests\Resources;


use Seier\Resting\Fields\StringField;

class UnionResourceA extends UnionResourceBase
{

    public $value;
    public $a;
    public $discriminator;

    public function __construct()
    {
        parent::__construct();

        $this->value = (new StringField)->required();
        $this->a = new StringField;
        $this->discriminator = new StringField;
    }
}