<?php

namespace Seier\Resting\ResourceValidation;

use Seier\Resting\Fields\Field;

class ResourceAttributeComparisonOperandDescription
{
    public ?Field $field;
    public ?string $fieldName;
    public mixed $value;
    public bool $realized;

    public function __construct(?Field $field, ?string $fieldName, mixed $value, bool $realized)
    {
        $this->field = $field;
        $this->fieldName = $fieldName;
        $this->value = $value;
        $this->realized = $realized;
    }
}