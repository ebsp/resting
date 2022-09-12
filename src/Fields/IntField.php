<?php

namespace Seier\Resting\Fields;

use Seier\Resting\Parsing\IntParser;
use Seier\Resting\Validation\IntValidator;
use Seier\Resting\Validation\Secondary\Enum\EnumValidation;
use Seier\Resting\Validation\Secondary\Numeric\NumericValidation;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

class IntField extends Field
{
    use NumericValidation;
    use EnumValidation;

    private IntValidator $validator;
    private IntParser $parser;

    public function __construct()
    {
        parent::__construct();

        $this->validator = new IntValidator();
        $this->parser = new IntParser();
    }

    public function getValidator(): IntValidator
    {
        return $this->validator;
    }

    public function getParser(): IntParser
    {
        return $this->parser;
    }

    public function get(): ?int
    {
        return $this->value;
    }

    public function set($value): static
    {
        if (is_float($value) && floor($value) === $value) {
            return parent::set((int)$value);
        }

        return parent::set($value);
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        return $this->validator;
    }

    public function type(): array
    {
        return [
            'type' => 'integer',
            'format' => 'int64',
        ];
    }
}