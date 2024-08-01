<?php

namespace Seier\Resting\Fields;

use Seier\Resting\Parsing\Parser;
use Seier\Resting\Parsing\RawParser;
use Seier\Resting\Validation\RawValidator;
use Seier\Resting\Validation\PrimaryValidator;
use Seier\Resting\Exceptions\RestingRuntimeException;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

class RawField extends Field
{
    private RawParser $parser;
    private RawValidator $validator;

    public function __construct()
    {
        parent::__construct();

        $this->parser = new RawParser();
        $this->validator = new RawValidator();
    }

    public function getParser(): Parser
    {
        return $this->parser;
    }

    public function getValidator(): PrimaryValidator
    {
        return $this->validator;
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        throw new RestingRuntimeException('Unsupported RawField::getSupportsSecondaryValidation');
    }

    public function type(): array
    {
        return [
            'type' => 'any'
        ];
    }
}