<?php

namespace Seier\Resting\Exceptions;

class InvalidJsonException extends RestingRuntimeException
{

    public function __construct()
    {
        parent::__construct("The route did not receive valid json.");
    }

    public function getStatus(): int
    {
        return 422;
    }
}