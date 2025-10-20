<?php

namespace Seier\Resting\ResourceValidation;

use Seier\Resting\Resource;
use Seier\Resting\Fields\Field;
use Seier\Resting\Fields\IntField;
use Seier\Resting\Fields\BoolField;
use Seier\Resting\Fields\TimeField;
use Seier\Resting\Fields\NumberField;
use Seier\Resting\Fields\StringField;
use Seier\Resting\Fields\CarbonField;
use Seier\Resting\Support\FormatsValues;
use Seier\Resting\Exceptions\RestingDefinitionException;

class ResourceAttributeComparisonValidator implements ResourceValidator
{
    use FormatsValues;

    private Resource $resource;
    private ResourceAttributeComparisonOperator $operator;
    private array $leftOperands;
    private array $rightOperands;

    public function __construct(Resource $resource, ResourceAttributeComparisonOperator $operator, array $left, array $right)
    {
        $this->resource = $resource;
        $this->operator = $operator;
        $this->leftOperands = $left;
        $this->rightOperands = $right;

        if (count($left) !== count($right)) {
            throw new RestingDefinitionException(
                message: "ResourceAttributeComparisonValidator does not support different number of operands on each side of comparison.",
            );
        }

        foreach ([...$left, ...$right] as $operand) {

            if (
                $operand instanceof NumberField ||
                $operand instanceof IntField ||
                $operand instanceof StringField ||
                $operand instanceof BoolField ||
                $operand instanceof TimeField ||
                $operand instanceof CarbonField
            ) {
                continue;
            }

            if ($operand instanceof Field) {
                $fieldType = $operand::class;
                throw new RestingDefinitionException(
                    message: "ResourceAttributeComparisonValidator does not support field type $fieldType"
                );
            }

            if ($operand !== null && !is_scalar($operand)) {
                throw new RestingDefinitionException(
                    message: "ResourceAttributeComparisonValidator only supports scalar operands or scalar resource fields.",
                );
            }

        }
    }

    public function description(): string
    {
        return $this->generateValidationDescription(
            $this->createOperandDescriptions($this->leftOperands),
            $this->createOperandDescriptions($this->rightOperands),
        );
    }

    public function validate(): array
    {
        if ($this->skip()) {
            return [];
        }

        $realizedLeftOperands = $this->formatOperands($this->leftOperands);
        $realizedRightOperands = $this->formatOperands($this->rightOperands);

        if ($this->isValid($realizedLeftOperands, $realizedRightOperands)) {
            return [];
        }

        return [new ResourceAttributeComparisonValidationError(
            operator: $this->operator,
            message: join(' ', [
                'Validation failed:',
                $this->generateValidationDescription(
                    $this->createOperandDescriptions($this->leftOperands, realizedOperands: $realizedLeftOperands),
                    $this->createOperandDescriptions($this->rightOperands, realizedOperands: $realizedRightOperands),
                )
            ])
        )];
    }

    private function createOperandDescriptions(array $operands, ?array $realizedOperands = null): array
    {
        $descriptions = [];
        foreach ($operands as $index => $leftOperand) {
            $descriptions[] = new ResourceAttributeComparisonOperandDescription(
                field: $field = ($leftOperand instanceof Field ? $leftOperand : null),
                fieldName: $field ? $this->resource->getFieldNameFromFieldObject($field) : null,
                value: $field
                    ? $realizedOperands !== null ? $realizedOperands[$index] : null
                    : $leftOperand,
                realized: $realizedOperands !== null,
            );
        }

        return $descriptions;
    }

    private function isValid(array $formattedLeftOperands, array $formattedRightOperands): bool
    {
        return match ($this->operator) {
            ResourceAttributeComparisonOperator::GreaterThan => $formattedLeftOperands > $formattedRightOperands,
            ResourceAttributeComparisonOperator::GreaterThanOrEqual => $formattedLeftOperands >= $formattedRightOperands,
            ResourceAttributeComparisonOperator::LessThan => $formattedLeftOperands < $formattedRightOperands,
            ResourceAttributeComparisonOperator::LessThanOrEqual => $formattedLeftOperands <= $formattedRightOperands,
            ResourceAttributeComparisonOperator::Equal => $formattedLeftOperands == $formattedRightOperands,
        };
    }

    private function formatOperands(array $operands): array
    {
        $results = [];
        foreach ($operands as $operand) {
            $results[] = $operand instanceof Field
                ? $operand->formatted()
                : $operand;
        }

        return $results;
    }

    private function generateValidationDescription(array $leftOperandDescriptions, array $rightOperandDescriptions): string
    {
        $operationDescription = match ($this->operator) {
            ResourceAttributeComparisonOperator::GreaterThan => 'must be greater than',
            ResourceAttributeComparisonOperator::GreaterThanOrEqual => 'must be greater than or equal',
            ResourceAttributeComparisonOperator::LessThan => 'must be less than',
            ResourceAttributeComparisonOperator::LessThanOrEqual => 'must be less than or equal',
            ResourceAttributeComparisonOperator::Equal => 'must be equal to',
        };

        return join(' ', [
            $this->formatOperandDescriptions($leftOperandDescriptions),
            $operationDescription,
            $this->formatOperandDescriptions($rightOperandDescriptions),
        ]);
    }

    private function formatOperandDescriptions(array $operandDescriptions): string
    {
        if (count($operandDescriptions) === 1) {
            return $this->formatOperandDescription($operandDescriptions[0]);
        }

        return join(' ', [
            '[',
            join(', ', array_map(
                fn (ResourceAttributeComparisonOperandDescription $operandDescription) => $this->formatOperandDescription($operandDescription),
                $operandDescriptions
            )),
            ']'
        ]);
    }

    private function formatOperandDescription(ResourceAttributeComparisonOperandDescription $description): string
    {
        if ($description->fieldName !== null) {
            $formattedValue = $this->format($description->value, showType: false);

            if (!$description->realized) {
                return "$description->fieldName";
            }

            return "$description->fieldName ($formattedValue)";
        }

        return $this->format($description->value);
    }

    private function skip(): bool
    {
        $hasField = false;
        $hasNonNullField = false;

        foreach ([...$this->leftOperands, ...$this->rightOperands] as $operand) {
            if ($operand instanceof Field) {
                $hasField = true;
                if ($operand->isNotNull()) {
                    $hasNonNullField = true;
                }
            }
        }

        return $hasField && !$hasNonNullField;
    }
}