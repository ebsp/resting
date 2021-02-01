<?php

namespace Seier\Resting\Tests;

use Seier\Resting\DynamicResource;
use Seier\Resting\Fields\StringField;
use Jchook\AssertThrows\AssertThrows;
use Seier\Resting\Exceptions\DynamicResourceFieldException;

class DynamicResourceTest extends TestCase
{

    use AssertThrows;

    private DynamicResource $instance;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new DynamicResource();
    }

    public function testFieldCanBeAdded()
    {
        $this->instance->withField('name', new StringField);

        $this->assertTrue($this->instance->fields()->has('name'));
    }

    public function testFieldCanBeAccessed()
    {
        $this->instance->withField('name', new StringField);

        $this->assertInstanceOf(StringField::class, $this->instance->name);
    }

    public function testToArray()
    {
        $this->instance->withField('name', new StringField);
        $this->instance->name->set($name = $this->faker->name);

        $this->assertEquals(['name' => $name], $this->instance->toArray());
    }

    public function testToResponseArray()
    {
        $this->instance->withField('name', new StringField);
        $this->instance->name->set($name = $this->faker->name);

        $this->assertEquals(['name' => $name], $this->instance->toResponseArray());
    }

    public function testAccessUnknownField()
    {
        $this->assertThrows(DynamicResourceFieldException::class, function () {
            $this->instance->unknown;
        });
    }
}
