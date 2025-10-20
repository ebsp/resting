<?php

namespace Seier\Resting\ResourceValidation;

use Seier\Resting\Resource;
use Seier\Resting\Fields\Field;
use Seier\Resting\Exceptions\RestingDefinitionException;
use Seier\Resting\Validation\Secondary\Anonymous\AnonymousValidationError;

class RequireOneFieldResourceValidator implements ResourceValidator
{
    private readonly Resource $resource;
    private readonly array $fieldGroup;

    public function __construct(Resource $resource, array $fieldGroup)
    {
        $this->resource = $resource;
        $this->fieldGroup = $fieldGroup;

        foreach ($fieldGroup as $field) {
            if (!($field instanceof Field)) {
                throw new RestingDefinitionException('RequireOneFieldResourceValidator must only be provided instances of Field.');
            }
        }

    }

    public function description(): string
    {
        $fieldNames = [];
        foreach ($this->fieldGroup as $field) {
            $fieldNames[] = $this->resource->getFieldNameFromFieldObject($field);
        }

        return "Only one of the following fields must be provided: " . implode(', ', $fieldNames);
    }

    public function validate(): array
    {
        $providedFieldNames = [];
        foreach ($this->fieldGroup as $field) {
            if ($field->isProvided()) {
                $providedFieldNames[] = $this->resource->getFieldNameFromFieldObject($field);
            }
        }

        if (count($providedFieldNames) === 0) {
            $description = $this->description();
            return [new AnonymousValidationError(
                message: "$description, but none of the fields were provided.",
            )];
        }

        if (count($providedFieldNames) > 1) {
            $description = $this->description();
            $providedFieldNamesList = implode(',', $providedFieldNames);
            return [new AnonymousValidationError(
                message: "$description, but the following fields were all provided $providedFieldNamesList.",
            )];
        }

        return [];
    }
}