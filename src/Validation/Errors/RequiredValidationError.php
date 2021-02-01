<?php


namespace Seier\Resting\Validation\Errors;


use Seier\Resting\Support\HasPath;

class RequiredValidationError implements ValidationError
{

    use HasPath;

    public function getMessage(): string
    {
        return "Value is required, but was not received.";
    }
}