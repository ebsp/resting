<?php

namespace Seier\Resting\Tests\Rules;

use Carbon\Carbon;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\Rules\DatePeriodRule;

class DatePeriodRuleTest extends TestCase
{
    public function testValidationFailsWrongType()
    {
        $rule = new DatePeriodRule;
        $this->assertNotNull($rule->passes(null, 'string'));

        $this->assertEquals([
            'period_starts' => ['validation.date'],
        ], $rule->message());
    }

    public function testValidationFailsWhenPeriodStartsAfterItEnds()
    {
        $rule = new DatePeriodRule;
        $this->assertFalse($rule->passes(null, [Carbon::create(2020, 1, 10), Carbon::create(2020, 1, 1)]));

        $this->assertEquals([
            'period_ends' => ['validation.after_or_equal'],
        ], $rule->message());
    }

    public function testValidationPasses()
    {
        $rule = new DatePeriodRule;
        $this->assertTrue($rule->passes(null, [Carbon::create(2020, 1, 1), Carbon::create(2020, 1, 10)]));
        $this->assertNull($rule->message());
    }

    public function testValidationPassesSingleDate()
    {
        $rule = new DatePeriodRule;
        $this->assertTrue($rule->passes(null, [Carbon::create(2020, 1, 1)]));
        $this->assertNull($rule->message());
    }
}
