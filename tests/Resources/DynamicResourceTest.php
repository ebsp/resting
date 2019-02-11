<?php

namespace Seier\Resting\Tests\Resources;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Seier\Resting\DynamicResource;
use Seier\Resting\Fields\StringField;

class DynamicResourceTest extends TestCase
{
    public function testInstantiation()
    {
        $this->assertInstanceOf(DynamicResource::class, new DynamicResource);
        $this->assertInstanceOf(DynamicResource::class, DynamicResource::create());
        $this->assertInstanceOf(DynamicResource::class, DynamicResource::fromArray([]));
        $this->assertInstanceOf(DynamicResource::class, DynamicResource::fromCollection(collect()));
    }

    public function testFieldCanBeAdded()
    {
        $resource = new DynamicResource;
        $resource->addField('name', new StringField);
        $this->assertTrue($resource->fields()->has('name'));
    }

    public function testAddedFieldsCanBeAccessed()
    {
        $resource = new DynamicResource;
        $resource->addField('name', new StringField);
        $this->assertInstanceOf(StringField::class, $resource->name);
    }

    public function testAddedFieldsAreResponded()
    {
        $resource = new DynamicResource;
        $resource->addField('name', new StringField);
        $resource->name->set('Emil');
        $response = json_decode($resource->toResponse(new Request)->getContent());

        $this->assertObjectHasAttribute('data', $response);
        $this->assertObjectHasAttribute('name', $response->data);
        $this->assertEquals('Emil', $response->data->name);
    }

    public function testFlattenMethod()
    {
        $resource = new DynamicResource;
        $resource->addField('name', new StringField);
        $resource->name->set('Emil');
        $flat = $resource->flatten();

        $this->assertObjectHasAttribute('name', $flat);
        $this->assertEquals($flat->name, 'Emil');
    }

    public function testFieldsCanBeRemoved()
    {
        $resource = new DynamicResource;
        $resource->addField('name', new StringField);
        $resource->removeField('name');
        $this->assertEmpty($resource->fields()->toArray());
    }
}
