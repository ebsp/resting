<?php

namespace Seier\Resting\Support;

trait SilenceErrorsTrait
{
    protected $shouldThrowErrors = true;

    public function throwErrors($should = true)
    {
        $this->shouldThrowErrors = $should;

        return $this;
    }
}