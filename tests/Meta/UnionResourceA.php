<?php


namespace Seier\Resting\Tests\Meta;


use Seier\Resting\Fields\StringField;

class UnionResourceA extends UnionResourceBase
{

    public StringField $a;

    public function __construct()
    {
        parent::__construct();

        $this->a = (new StringField)->required();
    }
}