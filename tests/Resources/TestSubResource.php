<?php

namespace Seier\Resting\Tests\Resources;

use Seier\Resting\Resource;
use Seier\Resting\Fields\IntField;

class TestSubResource extends Resource
{
    public $id;

    public function __construct()
    {
        $this->id = new IntField;
    }
}