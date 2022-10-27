<?php

namespace Seier\Resting\Support;

use Seier\Resting\Resource;

interface Transformer
{
    public function __invoke(Resourcable $resourcable): Resource;
}
