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

    /**
     * Validates if the current value it TRUE
     *
     * @param boolean $strict If should be "TRUE" or any value representing true, eg. true|1|'not empty string'
     * @return boolean
     */
    public function isTrue(bool $strict = true): bool
    {
        return $strict ? $this->value === true : $this->value == true;
    }

    /**
     * Validates if the current value it FALSE
     *
     * @param boolean $strict If should be "FALSE" or any value representing false, eg. false|null|0|''
     * @return boolean
     */
    public function isFalse(bool $strict = true): bool
    {
        return $strict ? $this->value === false : $this->value == false;
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        return $this->validator;
    }
}
