<?php

namespace Seier\Resting\Support;

use Exception;

trait SuppressErrorsTrait
{
    protected $suppressErrors = false;

    public function suppressErrors($should = true)
    {
        $this->suppressErrors = $should;

        return $this;
    }

    protected function error(Exception $exception)
    {
        if (! $this->suppressErrors) {
            throw $exception;
        }
    }
}
