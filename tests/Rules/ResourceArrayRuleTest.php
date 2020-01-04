<?php

namespace Seier\Resting\Tests\Rules;

use Seier\Resting\Tests\TestCase;
use Seier\Resting\Rules\ResourceArrayRule;
use Seier\Resting\Tests\Resources\TestResource;

class ResourceArrayRuleTest extends TestCase
{
    public function testValidation()
    {
        $resource1 = (new TestResource)->alwaysExpectRequired();
        $resource1->_string->required();

        $resource2 = (new TestResource)->alwaysExpectRequired();
        $resource2->_string->required();

        $rule = new ResourceArrayRule(new TestResource);

        $this->assertFalse($rule->passes(null, [$resource1, $resource2]));

        $messages = $rule->message();

        $this->assertEquals(2, count($messages));
        $this->assertArrayHasKey('_0', $messages);
        $this->assertArrayHasKey('_string', $messages['_0']);
        $this->assertIsArray($messages['_0']);
        $this->assertArrayHasKey('_1', $messages);
        $this->assertArrayHasKey('_string', $messages['_1']);
        $this->assertIsArray($messages['_1']);
    }
}
