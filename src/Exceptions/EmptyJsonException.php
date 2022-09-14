<?php

namespace Seier\Resting\Exceptions;

class EmptyJsonException extends RestingRuntimeException
{
    public function __construct()
    {
        parent::__construct("The route received body with no parameters");
    }

    public function getStatus(): int
    {
        return 422;
    }
}
