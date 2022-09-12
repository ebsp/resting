<?php

namespace Seier\Resting\Exceptions;

class EnvFunctionMissing extends RestingException
{
    public function __construct()
    {
        return parent::__construct('Could not find nor use env() function');
    }

    public static function cast()
    {
        return new static();
    }
}