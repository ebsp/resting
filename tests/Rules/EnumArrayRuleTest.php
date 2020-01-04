<?php

namespace Seier\Resting\Tests\Rules;

use Seier\Resting\Tests\TestCase;
use Seier\Resting\Rules\EnumArrayRule;

class EnumArrayRuleTest extends TestCase
{
    public function testValidationFailsWrongType()
    {
        $rule = new EnumArrayRule(['john', 'doe']);

        $this->assertFalse($rule->passes(null, false));
        $this->assertEquals('validation.invalid_enum_array', $rule->message());
    }

    public function testValidationFailsWrongOption()
    {
        $rule = new EnumArrayRule(['john', 'doe']);

        $this->assertFalse($rule->passes(null, 'invalid option'));
        $this->assertEquals('validation.invalid_enum_array', $rule->message());
    }

    public function testValidationPassesOption()
    {
        $rule = new EnumArrayRule(['john', 'doe']);

        $this->assertTrue($rule->passes(null, ['john']));
    }
}
