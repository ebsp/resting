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

    public static function fromRequest(Request $request, bool $suppressErrors = false)
    {
        return static::fromArray($request->query(), $suppressErrors)->setRequest($request);
    }

    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }
}
