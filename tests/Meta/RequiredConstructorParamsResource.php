<?php

namespace Seier\Resting\Tests\Meta;

use Seier\Resting\Resource;
use Seier\Resting\Fields\StringField;

class RequiredConstructorParamsResource extends Resource
{

    public StringField $name;

    public function __construct(string $requiredParam)
    {
        $this->name = new StringField;
    }
}
