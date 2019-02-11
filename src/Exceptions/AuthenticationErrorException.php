<?php

namespace Seier\Resting\Exceptions;

class AuthenticationErrorException extends Exception
{
    public function getStatus()
    {
        return 401;
    }
}