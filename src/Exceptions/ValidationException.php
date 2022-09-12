<?php

namespace Seier\Resting\Exceptions;

class ValidationException extends RestingRuntimeException
{
    private array $errors;

    public function __construct(array $errors)
    {
        parent::__construct();

        $this->errors = $errors;
        $this->message = $this->createMessage($errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    private function createMessage(array $errors): string
    {
        $message = 'There were validation errors:';
        foreach ($errors as $error) {

            $errorPath = $error->getPath();
            $errorMessage = $error->getMessage();

            $message .= "\n\t";
            $message .= "at path '$errorPath': $errorMessage";
        }

        return $message;
    }
}