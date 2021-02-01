<?php


namespace Seier\Resting\Validation\Secondary\Anonymous;


use Seier\Resting\Support\HasPath;
use Seier\Resting\Validation\Errors\ValidationError;

class AnonymousValidationError implements ValidationError
{

    use HasPath;

    private string $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}