<?php

namespace Seier\Resting\Tests\ResourceValidation;

use Seier\Resting\Resource;
use Seier\Resting\Fields\IntField;
use Seier\Resting\Fields\BoolField;
use Seier\Resting\Fields\TimeField;
use Seier\Resting\Fields\StringField;
use Seier\Resting\Fields\NumberField;
use Seier\Resting\Fields\CarbonField;

class ResourceAttributeComparisonTestResource extends Resource
{
    public IntField $int_field_a;
    public IntField $int_field_b;

    public StringField $string_field_a;
    public StringField $string_field_b;

    public BoolField $bool_field_a;
    public BoolField $bool_field_b;

    public NumberField $number_field_a;
    public NumberField $number_field_b;

    public TimeField $time_field_a;
    public TimeField $time_field_b;

    public CarbonField $carbon_field_a;
    public CarbonField $carbon_field_b;

    public CarbonField $date_field_a;
    public CarbonField $date_field_b;

    public function __construct()
    {
        $this->int_field_a = new IntField;
        $this->int_field_b = new IntField;

        $this->string_field_a = new StringField;
        $this->string_field_b = new StringField;

        $this->bool_field_a = new BoolField;
        $this->bool_field_b = new BoolField;

        $this->number_field_a = new NumberField;
        $this->number_field_b = new NumberField;

        $this->time_field_a = new TimeField;
        $this->time_field_b = new TimeField;

        $this->carbon_field_a = new CarbonField;
        $this->carbon_field_b = new CarbonField;

        $this->date_field_a = (new CarbonField)->withIsoDateFormat();
        $this->date_field_b = (new CarbonField)->withIsoDateFormat();
    }
}