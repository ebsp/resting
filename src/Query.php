<?php

namespace Seier\Resting;

use Illuminate\Http\Request;

abstract class Query extends Resource
{
    protected $request;

    public function requiredFieldsExpected(Request $request)
    {
        return true;
    }

    public static function fromRequest(Request $request)
    {
        return static::fromArray($request->query(), false)->setRequest($request);
    }

    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }
}
