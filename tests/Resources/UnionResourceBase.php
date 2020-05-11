<?php


namespace Seier\Resting\Tests\Resources;


use Seier\Resting\Fields\StringField;
use Seier\Resting\UnionResource;

class UnionResourceBase extends UnionResource
{

    public $discriminator;

    public function __construct()
    {
        parent::__construct('discriminator', fn() => [
            'a' => new UnionResourceA(),
            'b' => new UnionResourceB(),
        ]);

        $this->discriminator = new StringField;
    }
}