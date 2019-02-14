<?php

namespace Seier\Resting;

use Illuminate\Http\Request;
use Seier\Resting\Fields\FieldAbstract;

abstract class Params extends Resource {

    public function requiredFieldsExpected(Request $request)
    {
        return true;
    }

    public static function fromRequest(Request $request)
    {
        $params = static::fromArray($request->route()->originalParameters(), false);

        $params->fields()->each(function (FieldAbstract $field, $name) use ($request) {
            $request->route()->forgetParameter($name);
        });

        return static::fromArray($request->route()->originalParameters(), false);
    }
}
