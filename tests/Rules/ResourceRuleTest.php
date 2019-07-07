<?php

namespace Seier\Resting\Tests\Rules;

use Seier\Resting\Tests\TestCase;
use Seier\Resting\Rules\ResourceRule;
use Seier\Resting\Tests\Resources\TestResource;

class ResourceRuleTest extends TestCase
{
    public function testValidation()
    {
        $resource = new TestResource;
        $resource->_string->required();

        $rule = new ResourceRule($resource);
        $this->assertFalse($rule->passes(null, $resource));
        $this->assertArrayHasKey('_string', $rule->message());
        $this->assertEquals(1, count($rule->message()));
    }
}
