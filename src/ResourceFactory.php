<?php

namespace Seier\Resting;

use Seier\Resting\Resource as RestingResource;

interface ResourceFactory
{
    public function create(): RestingResource;
}