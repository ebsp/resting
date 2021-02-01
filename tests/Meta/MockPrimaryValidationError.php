<?php


namespace Seier\Resting\Tests\Meta;


use Seier\Resting\Support\HasPath;
use Seier\Resting\Validation\Errors\ValidationError;

class MockPrimaryValidationError implements ValidationError
{

    use HasPath;

    public function getMessage(): string
    {
        return "mock";
    }
}