<?php

namespace Seier\Resting\Fields;

use Seier\Resting\Parsing\Parser;
use Seier\Resting\Parsing\StringParser;
use Seier\Resting\Validation\StringValidator;
use Seier\Resting\Parsing\DefaultParseContext;
use Seier\Resting\Exceptions\ValidationException;
use Seier\Resting\Validation\Secondary\Enum\EnumValidation;
use Seier\Resting\Validation\Secondary\String\StringValidation;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

class StringField extends Field
{

    use StringValidation;
    use EnumValidation;

    private StringValidator $validator;
    private StringParser $parser;

    public function __construct()
    {
        parent::__construct();

        $this->validator = new StringValidator();
        $this->parser = new StringParser();
    }

    public function getValidator(): StringValidator
    {
        return $this->validator;
    }

    public function getParser(): ?Parser
    {
        return $this->parser;
    }

    public function set($value): static
    {
        $parseContext = new DefaultParseContext($value, false);
        if ($this->parser->shouldParse($parseContext)) {
            $errors = $this->parser->canParse($parseContext);
            if ($errors) {
                throw new ValidationException($errors);
            }

            $value = $this->parser->parse($parseContext);
        }

        return parent::set($value);
    }

    public function get(): ?string
    {
        return $this->value;
    }

    public function getNotEmpty(bool $trim = false): ?string
    {
        $value = $this->value;
        if ($trim && is_string($value)) {
            $value = trim($value);
        }

        return empty($value)
            ? null
            : $value;
    }

    public function emptyStringAsNull(bool $state = true): static
    {
        $this->parser->emptyStringAsNull($state);
        if ($state) {
            $this->nullable();
        }

        return $this;
    }

    public function type(): array
    {
        return [
            'type' => 'string',
        ];
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        return $this->validator;
    }
}
