<?php

namespace Seier\Resting;

use Illuminate\Http\Request;

abstract class Query extends Resource {

    public function requiredFieldsExpected(Request $request)
    {
        return true;
    }
}
