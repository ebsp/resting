<?php


namespace Seier\Resting\Tests\Validation\Secondary\Anonymous;


use Seier\Resting\Validation\PrimaryValidator;
use Seier\Resting\Validation\Secondary\SupportsSecondaryValidation;
use Seier\Resting\Validation\Secondary\Anonymous\AnonymousValidation;

class AnonymousValidationTestBench
{

    use AnonymousValidation;

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