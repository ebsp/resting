<?php

namespace Seier\Resting\Validation\Errors;

use Seier\Resting\Support\HasPath;

class CarbonPeriodEndRequiredValidationError implements ValidationError
{
    use HasPath;

    public function getMessage(): string
    {
        return "End of period is required, but was not provided.";
    }

}