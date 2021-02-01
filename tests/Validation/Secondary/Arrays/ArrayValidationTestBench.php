<?php


namespace Seier\Resting\Tests\Validation\Secondary\Arrays;


use Seier\Resting\Validation\PrimaryValidator;
use Seier\Resting\Validation\Secondary\Arrays\ArrayValidation;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;

class ArrayValidationTestBench
{

    use ArrayValidation;

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