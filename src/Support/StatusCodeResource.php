<?php

namespace Seier\Resting\Support;

use Seier\Resting\Resource;

class StatusCodeResource extends Resource
{
    protected $_responseCode = 204;

    public static function status($code)
    {
        $instance = new static;
        $instance->_responseCode = $code;

        return $instance;
    }
}
