<?php


namespace Seier\Resting\Tests\Validation\Secondary\In;


use Seier\Resting\Validation\PrimaryValidator;
use Seier\Resting\Validation\Secondary\In\InValidation;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

class InValidationTestBench
{

    use InValidation;

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