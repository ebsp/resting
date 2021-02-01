<?php

namespace Seier\Resting\Fields;

use Seier\Resting\Parsing\Parser;
use Seier\Resting\Parsing\StringParser;
use Seier\Resting\Validation\StringValidator;
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

    public function get(): ?string
    {
        return $this->value;
    }

    public function getNotEmpty(): ?string
    {
        return empty($this->value)
            ? null
            : $this->value;
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
