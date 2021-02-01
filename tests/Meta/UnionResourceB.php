<?php


namespace Seier\Resting\Tests\Meta;


use Seier\Resting\Fields\StringField;

class UnionResourceB extends UnionResourceBase
{

    public StringField $b;

    public function __construct()
    {
        parent::__construct();

        $this->b = (new StringField)->required();
    }
}