<?php

namespace Seier\Resting\Support;

use Seier\Resting\Resource;

class BaseTransformer implements Transformer
{
    public function __invoke(Resourcable $resourcable): Resource
    {
        return $resourcable->asResource();
    }
}
