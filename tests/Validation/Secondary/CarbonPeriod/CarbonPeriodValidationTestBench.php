<?php


namespace Seier\Resting\Tests\Validation\Secondary\CarbonPeriod;


use Seier\Resting\Validation\PrimaryValidator;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;
use Seier\Resting\Validation\Secondary\CarbonPeriod\CarbonPeriodValidation;

class CarbonPeriodValidationTestBench
{

    use CarbonPeriodValidation;

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