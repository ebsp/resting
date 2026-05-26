<?php

namespace Seier\Resting\Tests\Meta;

use Seier\Resting\UnionResource;
use Seier\Resting\Fields\StringField;

class EmptyUnionResource extends UnionResource
{
    public StringField $discriminator;

    public function __construct()
    {
        parent::__construct('discriminator', fn () => []);

        $this->discriminator = new StringField;
    }
}
