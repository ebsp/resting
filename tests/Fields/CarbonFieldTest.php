<?php

namespace Seier\Resting\Tests\Fields;

use Carbon\Carbon;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\Fields\CarbonField;

class CarbonFieldTest extends TestCase
{

    public function testCasting()
    {
        $field = new CarbonField;
        $field->set('1970-01-01');
        $this->assertInstanceOf(Carbon::class, $field->get());
    }

    public function testNullIsNotCasted()
    {
        $field = new CarbonField;
        $field->set(null);
        $this->assertNull($field->get());
    }

    public function testNonNullable()
    {
        $field = (new CarbonField)->nullable(false);
        $this->assertInstanceOf(Carbon::class, $field->get());
    }
}
