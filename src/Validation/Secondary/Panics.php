<?php


namespace Seier\Resting\Validation\Secondary;


use Seier\Resting\Exceptions\RestingInternalException;

trait Panics
{

    public function panic()
    {
        throw new RestingInternalException();
    }
}