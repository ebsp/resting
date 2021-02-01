<?php


namespace Seier\Resting\Tests\Meta;


use Seier\Resting\UnionResource;
use Seier\Resting\Fields\StringField;

class UnionResourceBase extends UnionResource
{

    public StringField $discriminator;
    public StringField $value;

    public function __construct()
    {
        parent::__construct('discriminator', fn() => [
            'a' => new UnionResourceA(),
            'b' => new UnionResourceB(),
        ]);

        $this->discriminator = new StringField;
        $this->value = new StringField;
    }
}