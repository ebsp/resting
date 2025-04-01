<?php

namespace Seier\Resting\Tests\Meta;

use Seier\Resting\Resource;
use Seier\Resting\Fields\ArrayField;

class SuiteResource extends Resource
{
    public ArrayField $suites;

    public function __construct()
    {
        $this->suites = (new ArrayField)->ofEnums(SuiteEnum::class);
    }
}
