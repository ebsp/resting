<?php

namespace Seier\Resting\Tests\Resources;

use Seier\Resting\Resource;
use Seier\Resting\Fields\IntField;
use Seier\Resting\Fields\DateField;
use Seier\Resting\Fields\EnumField;
use Seier\Resting\Fields\BoolField;
use Seier\Resting\Fields\ArrayField;
use Seier\Resting\Fields\CarbonField;
use Seier\Resting\Fields\HiddenField;
use Seier\Resting\Fields\NumberField;
use Seier\Resting\Fields\StringField;
use Seier\Resting\Fields\PasswordField;
use Seier\Resting\Fields\ResourceField;
use Seier\Resting\Fields\ResourceArrayField;

class TestResource extends Resource
{
    public $_array;
    public $_bool;
    public $_carbon;
    public $_date;
    public $_enum;
    public $_int;
    public $_number;
    public $_password;
    public $_resource_array;
    public $_resource;
    public $_string;
    public $_hidden;

    public function __construct()
    {
        $this->_array = new ArrayField;
        $this->_bool = new BoolField;
        $this->_carbon = new CarbonField;
        $this->_date = new DateField;
        $this->_enum = new EnumField(['john', 'doe']);
        $this->_int = new IntField;
        $this->_number = new NumberField;
        $this->_password = new PasswordField;
        $this->_resource_array = new ResourceArrayField(new TestSubResource);
        $this->_resource = new ResourceField(new TestSubResource);
        $this->_string = new StringField;
        $this->_hidden = new HiddenField;
    }
}

