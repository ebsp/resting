<?php

namespace Seier\Resting\ResourceValidation;

use Seier\Resting\Support\HasPath;
use Seier\Resting\Support\FormatsValues;
use Seier\Resting\Validation\Errors\ValidationError;

class ResourceAttributeComparisonValidationError implements ValidationError
{
    use FormatsValues;
    use HasPath;

    private ResourceAttributeComparisonOperator $operator;
    private string $message;

    public function __construct(ResourceAttributeComparisonOperator $operator, string $message)
    {
        $this->operator = $operator;
        $this->message = $message;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}