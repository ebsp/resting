<?php

namespace Seier\Resting\Exceptions;

class UnauthorizedException extends Exception
{
    public function getStatus()
    {
        return 401;
    }
}
