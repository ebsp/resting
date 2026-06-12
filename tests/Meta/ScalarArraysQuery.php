<?php

namespace Seier\Resting\Tests\Meta;

use Seier\Resting\Query;
use Seier\Resting\Fields\ArrayField;

class ScalarArraysQuery extends Query
{
    public ArrayField $ints;
    public ArrayField $strings;
    public ArrayField $bools;

    public function __construct()
    {
        $this->ints = (new ArrayField)->ofIntegers()->notRequired();
        $this->strings = (new ArrayField)->ofStrings()->notRequired();
        $this->bools = (new ArrayField)->ofBooleans()->notRequired();
    }
}
