<?php

namespace Seier\Resting\Exceptions;

use Closure;

class ValidationExceptionHandler
{
    private array $errors = [];

    public function suppress(array|string|int $path, Closure $function): mixed
    {
        try {
            return $function();
        } catch (ValidationException $exception) {
            foreach ($exception->getErrors() as $error) {
                $this->errors[] = $error->prependPath($path);
            }
        }

        return null;
    }

    public function rethrow()
    {
        if (count($this->errors)) {
            throw new ValidationException($this->errors);
        }
    }

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function moveErrors(array &$destination): static
    {
        array_push($destination, ...$this->errors);
        $this->clear();

        return $this;
    }

    public function clear(): static
    {
        $this->errors = [];

        return $this;
    }
}