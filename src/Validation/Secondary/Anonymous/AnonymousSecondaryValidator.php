<?php


namespace Seier\Resting\Validation\Secondary\Anonymous;


use Closure;
use Seier\Resting\Validation\FormatsValues;
use Seier\Resting\Validation\Errors\ValidationError;
use Seier\Resting\Exceptions\RestingDefinitionException;
use Seier\Resting\Validation\Secondary\SecondaryValidator;

class AnonymousSecondaryValidator implements SecondaryValidator
{

    use FormatsValues;

    private string $description;
    private Closure $validator;

    public function __construct(string $description, Closure $validator)
    {
        $this->description = $description;
        $this->validator = $validator;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function validate(mixed $value): array
    {
        $result = ($this->validator)($value);

        return $this->transformReturnValue($result);
    }

    private function transformReturnValue(mixed $result): array
    {
        if ($result instanceof ValidationError) {
            return [$result];
        }

        if ($result === true) {
            return [];
        }

        if ($result === null) {
            return [];
        }

        if ($result === false) {
            return [$this->createValidationError("The value did not pass validation; Value must conform to the following: $this->description.")];
        }

        if (is_string($result)) {
            return [$this->createValidationError($result)];
        }

        $formatted = $this->format($result);

        throw new RestingDefinitionException("Invalid value returned from custom validator, expected boolean, null, string or an instance of ValidationError, received $formatted");
    }

    private function createValidationError(string $message): AnonymousValidationError
    {
        return new AnonymousValidationError($message);
    }

    public function isUnique(): bool
    {
        return false;
    }
}