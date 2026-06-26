<?php

namespace Seier\Resting\Validation\Errors;

use Seier\Resting\Exceptions\ValidationException;

class RequestValidationErrors
{

    public function __construct(
        private array $body = [],
        private array $query = [],
        private array $param = [],
    )
    {
    }

    public function getBody(): array
    {
        return $this->body;
    }

    public function getQuery(): array
    {
        return $this->query;
    }

    public function getParam(): array
    {
        return $this->param;
    }

    public function all(): array
    {
        return array_merge($this->body, $this->query, $this->param);
    }

    public function isEmpty(): bool
    {
        return $this->all() === [];
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function toException(): ValidationException
    {
        return new ValidationException($this->all());
    }
}
