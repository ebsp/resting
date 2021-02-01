<?php


namespace Seier\Resting\Validation\Errors;


use Seier\Resting\Support\HasPath;

class NullableValidationError implements ValidationError
{

    use HasPath;

    public function getMessage(): string
    {
        return "Value is not nullable, but null was provided.";
    }
}