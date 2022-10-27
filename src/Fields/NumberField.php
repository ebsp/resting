<?php

namespace Seier\Resting\Fields;

use Seier\Resting\Parsing\NumberParser;
use Seier\Resting\Validation\NumberValidator;
use Seier\Resting\Validation\Secondary\Enum\EnumValidation;
use Seier\Resting\Validation\Secondary\Numeric\NumericValidation;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

class NumberField extends Field
{
    use NumericValidation;
    use EnumValidation;

    private NumberValidator $validator;
    private NumberParser $parser;

    public function __construct()
    {
        parent::__construct();

        $this->validator = new NumberValidator();
        $this->parser = new NumberParser();
    }

    public function getValidator(): NumberValidator
    {
        return $this->validator;
    }

    public function getParser(): NumberParser
    {
        return $this->parser;
    }

    public function get(): float|int|null
    {
        return $this->value;
    }

    public function type(): array
    {
        return [
            'type' => 'number',
        ];
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        return $this->validator;
    }
}