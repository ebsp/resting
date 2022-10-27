<?php

namespace Seier\Resting\Validation\Errors;

use Seier\Resting\Support\HasPath;

class ForbiddenValidationError implements ValidationError
{
    use HasPath;

    public function getMessage(): string
    {
        return "Field is forbidden, but a value was provided.";
    }
}