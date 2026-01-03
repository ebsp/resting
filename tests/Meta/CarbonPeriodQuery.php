<?php

namespace Seier\Resting\Tests\Meta;

use Seier\Resting\Query;
use Seier\Resting\Fields\CarbonPeriodField;

class CarbonPeriodQuery extends Query
{
    public CarbonPeriodField $period;

    public function __construct()
    {
        $this->period = (new CarbonPeriodField)->notRequired();
    }
}