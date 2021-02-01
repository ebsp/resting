<?php


namespace Seier\Resting\Tests\Meta;


use Seier\Resting\Resource;
use Seier\Resting\Fields\CarbonField;

class ActivityResource extends Resource
{

    public CarbonField $start;
    public CarbonField $end;

    public function __construct()
    {
        $this->start = new CarbonField;
        $this->end = new CarbonField;
    }
}