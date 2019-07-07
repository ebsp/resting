<?php

namespace Seier\Resting;

use Illuminate\Http\Request;
use Seier\Resting\Fields\FieldAbstract;

abstract class Params extends Resource {

    public function requiredFieldsExpected(Request $request)
    {
        return true;
    }

    public static function fromRequest(Request $request, bool $suppressErrors = false)
    {
        $params = static::fromArray($request->route()->originalParameters(), $suppressErrors);

        $params->fields()->each(function (FieldAbstract $field, $name) use ($request) {
            $request->route()->forgetParameter($name);
        });

        return static::fromArray($request->route()->originalParameters(), $suppressErrors);
    }
}
