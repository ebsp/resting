<?php

namespace Seier\Resting\Fields;

use Throwable;
use BackedEnum;
use ReflectionEnum;
use Seier\Resting\Parsing\Parser;
use Seier\Resting\Parsing\EnumParser;
use Seier\Resting\Validation\EnumValidator;
use Seier\Resting\Parsing\DefaultParseContext;
use Seier\Resting\Exceptions\ValidationException;
use Seier\Resting\Validation\Secondary\In\InValidation;
use Seier\Resting\Exceptions\RestingDefinitionException;
use Seier\Resting\Validation\Errors\EnumValidationError;
use Seier\Resting\Validation\Secondary\String\StringValidation;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

/**
 * Supports accepting/returning the valid values from a PHP-enum. Both in a string-based context, and when given PHP-based
 * enum values.
 *
 * @template T
 */
class EnumField extends Field
{

    use StringValidation;
    use InValidation;

    private EnumValidator $validator;
    private EnumParser $parser;

    private ReflectionEnum $reflectionEnum;
    private string $enum;

    /**
     * @param class-string<T> $enum The class-name of the Enum accepted/returned by the field.
     * @throws RestingDefinitionException When an unsupported enum is given. Only supports string-backed enums.
     */
    public function __construct(string $enum)
    {
        parent::__construct();

        $this->enum = $enum;
        $this->reflectionEnum = new ReflectionEnum($enum);

        $this->validator = new EnumValidator($this->reflectionEnum);
        $this->parser = new EnumParser($this->reflectionEnum);

        $reflectionEnum = new ReflectionEnum($enum);

        if (!$reflectionEnum->isBacked()) {
            throw new RestingDefinitionException("EnumField only supports enums backed by strings.");
        }

        foreach ($reflectionEnum->getCases() as $case) {
            if (!is_string($case->getBackingValue())) {
                throw new RestingDefinitionException("EnumField only supports enums backed by strings.");
            }
        }
    }

    public function getValidator(): EnumValidator
    {
        return $this->validator;
    }

    public function getParser(): ?Parser
    {
        return $this->parser;
    }

    public function set($value): static
    {
        if ($value === null) {
            return parent::set($value);
        }

        if (is_object($value) && $this->reflectionEnum->isInstance($value)) {
            return parent::set($value);
        }

        if (is_string($value)) {

            $rawValue = $value;

            $parseContext = new DefaultParseContext($value, false);
            if ($this->parser->shouldParse($parseContext)) {
                $errors = $this->parser->canParse($parseContext);
                if ($errors) {
                    throw new ValidationException([
                        new EnumValidationError($this->reflectionEnum, $rawValue)
                    ]);
                }

                $value = $this->parser->parse($parseContext);
            }


            if (is_object($value) && $this->reflectionEnum->isInstance($value)) {
                return parent::set($value);
            }

            try {
                $value = ($this->enum)::from($value);
            } catch (Throwable) {
                throw new ValidationException([
                    new EnumValidationError($this->reflectionEnum, $rawValue)
                ]);
            }
        }

        if (!($value instanceof $this->enum)) {
            throw new ValidationException([
                new EnumValidationError($this->reflectionEnum, $value)
            ]);
        }

        return parent::set($value);
    }

    /**
     * @return T|null
     */
    public function get(): ?BackedEnum
    {
        return parent::get();
    }

    public function type(): array
    {
        $options = [];
        foreach ($this->reflectionEnum->getCases() as $case) {
            $options[] = $case->getBackingValue();
        }

        return [
            'type' => 'string',
            'enum' => $options,
        ];
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        return $this->validator;
    }
}