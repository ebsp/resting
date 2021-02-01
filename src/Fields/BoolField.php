<?php

namespace Seier\Resting\Fields;

use Seier\Resting\Parsing\BoolParser;
use Seier\Resting\Validation\BoolValidator;
use Seier\Resting\Validation\Secondary\Enum\EnumValidation;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

class BoolField extends Field
{

    use EnumValidation;

    private BoolValidator $validator;
    private BoolParser $parser;

    public function __construct()
    {
        parent::__construct();

        $this->validator = new BoolValidator();
        $this->parser = new BoolParser();
    }

    public function getValidator(): BoolValidator
    {
        return $this->validator;
    }

    public function getParser(): BoolParser
    {
        return $this->parser;
    }

    public function get(): ?bool
    {
        return $this->value;
    }

    public function type(): array
    {
        return [
            'type' => 'boolean',
        ];
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        return $this->validator;
    }
}