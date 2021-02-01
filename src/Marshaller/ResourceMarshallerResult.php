<?php


namespace Seier\Resting\Marshaller;


use Seier\Resting\Validation\Errors\ValidationError;

class ResourceMarshallerResult
{

    private array $validationErrors;
    private mixed $result;

    public function __construct($value, array $validationErrors = [])
    {
        $this->validationErrors = $validationErrors;
        $this->result = $value;
    }

    public function getValue()
    {
        return $this->result;
    }

    public function getErrors(): array
    {
        return $this->validationErrors;
    }

    public function hasErrors(): bool
    {
        return count($this->validationErrors) > 0;
    }

    public function getErrorsForPath(string $path): array
    {
        return array_values(array_filter($this->validationErrors, function (ValidationError $error) use ($path) {
            return $error->getPath() === $path;
        }));
    }
}