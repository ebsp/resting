<?php

namespace Seier\Resting\Tests\Meta;

use Seier\Resting\Fields\RawField;
use Seier\Resting\Resource;

class RawFieldResource extends Resource
{
    public RawField $payload;

    public function __construct()
    {
        $this->payload = new RawField();
    }
}
