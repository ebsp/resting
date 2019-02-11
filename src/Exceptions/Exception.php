<?php

namespace Seier\Resting\Exceptions;

use RuntimeException;

class Exception extends RuntimeException
{
    public function getStatus()
    {
        return 500;
    }
}