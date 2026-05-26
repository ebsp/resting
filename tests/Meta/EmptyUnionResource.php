<?php

namespace Seier\Resting\Tests\Meta;

use Seier\Resting\UnionResource;
use Seier\Resting\Fields\StringField;

/**
 * A union resource whose dependent map is empty — used to exercise the
 * empty-oneOf guard in the OpenAPI generator.
 */
class EmptyUnionResource extends UnionResource
{
    public StringField $discriminator;

    public function __construct()
    {
        parent::__construct('discriminator', fn () => []);

        $this->discriminator = new StringField;
    }
}
