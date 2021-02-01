<?php


namespace Seier\Resting\Tests\Validation\Secondary\Enum;


use Seier\Resting\Validation\PrimaryValidator;
use Seier\Resting\Validation\Secondary\Enum\EnumValidation;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

class EnumValidationTestBench
{

    use EnumValidation;

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