<?php


namespace Seier\Resting\Tests\Resources;


use Seier\Resting\Fields\StringField;

class UnionResourceB extends UnionResourceBase
{

    public $value;
    public $b;

    public function __construct()
    {
        parent::__construct();

        $this->value = (new StringField)->required();
        $this->b = new StringField;
    }
}