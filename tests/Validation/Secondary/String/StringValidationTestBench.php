<?php


namespace Seier\Resting\Tests\Validation\Secondary\String;


use Seier\Resting\Validation\PrimaryValidator;
use Seier\Resting\Validation\Secondary\String\StringValidation;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

class StringValidationTestBench
{

    use StringValidation;

    private PrimaryValidator $primaryValidator;

    public function __construct(PrimaryValidator $primaryValidator)
    {
        $this->primaryValidator = $primaryValidator;
    }

    protected function getSupportsSecondaryValidation(): SupportsSecondaryValidation
    {
        return $this->primaryValidator;
    }
}