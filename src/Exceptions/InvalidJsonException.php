<?php

namespace Seier\Resting\Exceptions;

class InvalidJsonException extends Exception
{
    public function getStatus()
    {
        return 422;
    }
}