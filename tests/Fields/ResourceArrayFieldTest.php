<?php

namespace Seier\Resting\Tests\Fields;

use Seier\Resting\Tests\TestCase;
use Seier\Resting\Rules\ResourceArrayRule;
use Seier\Resting\Fields\ResourceArrayField;
use Seier\Resting\Exceptions\NotArrayException;
use Seier\Resting\Tests\Resources\TestResource;

class ResourceArrayFieldTest extends TestCase
{
    private function fieldInstance()
    {
        return new ResourceArrayField(new TestResource);
    }

    public function testValidation()
    {
        $field = $this->fieldInstance();
        $this->assertInstanceOf(ResourceArrayRule::class, $field->validation()[0]);
        $this->assertInstanceOf(TestResource::class, $field->validation()[0]->resource());
    }

    public function testInvalidValueValidation()
    {
        $field = $this->fieldInstance();
        $this->expectException(NotArrayException::class);
        $field->set(1);
    }

    public function testValueCanBeSet()
    {
        $field = $this->fieldInstance();
        $field->set($values = [new TestResource]);
        $this->assertInstanceOf(TestResource::class, $field->get()[0]);
    }

    public function testEmptyReturnsNull()
    {
        $field = $this->fieldInstance();
        $this->assertNull($field->get());
    }

    public function testNonNullableReturnsEmptyArrayA()
    {
        $field = $this->fieldInstance()->nullable(false);
        $this->assertEquals($field->get(), []);
    }
}
