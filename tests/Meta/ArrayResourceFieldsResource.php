<?php

namespace Seier\Resting\Tests\Meta;

use Seier\Resting\Resource;
use Seier\Resting\Fields\ResourceArrayField;

class ArrayResourceFieldsResource extends Resource
{
    public ResourceArrayField $persons;
    public ResourceArrayField $nullable_persons;

    public function __construct()
    {
        $this->persons = (new ResourceArrayField(fn () => new PersonResource));
        $this->nullable_persons = (new ResourceArrayField(fn () => new PersonResource))->allowNullElements();
    }
}