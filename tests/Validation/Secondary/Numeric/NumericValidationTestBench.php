<?php


namespace Seier\Resting\Tests\Validation\Secondary\Numeric;


use Seier\Resting\Validation\PrimaryValidator;
use Seier\Resting\Validation\Secondary\Numeric\NumericValidation;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

class NumericValidationTestBench
{

    use NumericValidation;

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