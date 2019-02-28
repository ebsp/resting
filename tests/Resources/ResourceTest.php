<?php

namespace Seier\Resting\Tests\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Illuminate\Http\JsonResponse;
use Seier\Resting\Fields\BoolField;
use Seier\Resting\Fields\DateField;
use Seier\Resting\Fields\ArrayField;
use Seier\Resting\Fields\NumberField;
use Seier\Resting\Fields\PasswordField;
use Seier\Resting\Fields\ResourceField;
use Seier\Resting\Fields\ResourceArrayField;
use Seier\Resting\Support\StatusCodeResource;

class ResourceTest extends TestCase
{
    public function testResourceInstantiation()
    {
        $this->assertInstanceOf(TestResource::class, new TestResource);
        $this->assertInstanceOf(TestResource::class, TestResource::create());
        $this->assertInstanceOf(TestResource::class, TestResource::fromArray([]));
        $this->assertInstanceOf(TestResource::class, TestResource::fromCollection(collect()));
    }

    public function testResourceFields()
    {
        $resource = new TestResource;

        $this->assertInstanceOf(ResourceArrayField::class, $resource->_resource_array);
        $this->assertInstanceOf(ResourceField::class, $resource->_resource);
        $this->assertInstanceOf(PasswordField::class, $resource->_password);
        $this->assertInstanceOf(NumberField::class, $resource->_number);
        $this->assertInstanceOf(DateField::class, $resource->_date);
        $this->assertInstanceOf(BoolField::class, $resource->_bool);
        $this->assertInstanceOf(ArrayField::class, $resource->_array);
    }

    private function resourceWithValues()
    {
        $resource = new TestResource;
        $resource->_int->set(10);
        $resource->_array->set(['item']);
        $resource->_bool->set(true);
        $resource->_date->set('2018-01-01');
        $resource->_number->set(13.9);
        $resource->_password->set('secret');
        $resource->_resource->set(new TestSubResource);
        $resource->_resource_array->set([new TestSubResource]);
        $resource->_carbon->set(Carbon::now());
        $resource->_string->set('a string');

        return $resource;
    }

    public function testFlattenedResource()
    {
        $resource = $this->resourceWithValues()->flatten();
        $this->assertTrue($resource->_bool);
        $this->assertEquals(['item'], $resource->_array);
        $this->assertInstanceOf(Carbon::class, $resource->_carbon);
        $this->assertEquals("2018-01-01", $resource->_date);
        $this->assertEquals(10, $resource->_int);
        $this->assertEquals('a string', $resource->_string);
        $this->assertInstanceOf(TestSubResource::class, $resource->_resource);
        $this->assertTrue(is_array($resource->_resource_array));
        $this->assertInstanceOf(TestSubResource::class, $resource->_resource_array[0]);
        $this->assertEquals(13.9, $resource->_number);
    }

    public function testToArray()
    {
        $this->assertTrue(is_array($this->resourceWithValues()->toArray()));
    }

    public function testResourceIsResponable()
    {
        $this->assertInstanceOf(JsonResponse::class, $this->resourceWithValues()->toResponse(new Request));
    }

    public function testTrimForNull()
    {
        $resource = new TestResource;
        $this->assertEquals(0, count($resource->toResponseArray()));
        $resource->_string->set('test');
        $this->assertEquals(1, count($resource->toResponseArray()));
    }

    public function testValidation()
    {
        $this->assertTrue(true);
        // validation
    }

    public function testToJson()
    {
        $resource = new TestResource;
        $resource->_string->set('random string');
        $resource->_int->set(100);

        $this->assertTrue(is_string($resource->toJson()));

        $decoded = json_decode($resource->toJson());

        $this->assertTrue(is_object($decoded));
        $this->assertEquals(0, json_last_error());
    }

    public function testInstantiationFromArray()
    {
        $resource = TestResource::fromArray([
            '_string' => 'testing',
            '_number' => 15.5,
            '_resource' => [
                'id' => 10,
            ],
        ]);

        $this->assertEquals('testing', $resource->_string->get());
        $this->assertEquals(15.5, $resource->_number->get());
        $this->assertEquals(10, $resource->_resource->get()->id);
    }

    public function testInstantiationFromCollection()
    {
        $resource = TestResource::fromCollection(collect([
            '_bool' => true,
            '_int' => 10,
            '_resource' => [
                'id' => 9,
            ],
        ]));

        $this->assertTrue($resource->_bool->get());
        $this->assertEquals(10, $resource->_int->get());
        $this->assertEquals(9, $resource->_resource->get()->id);
    }

    public function testAResourceCanDecideItsStatusCode()
    {
        $response = StatusCodeResource::create()->toResponse(new Request);
        $this->assertEquals(204, $response->status());
    }

    public function testDefaultResponseCode()
    {
        $response = TestResource::create()->toResponse(new Request);
        $this->assertEquals(200, $response->status());
    }

    public function testHiddenFieldsAreNotExposed()
    {
        $resource = new TestResource;
        $resource->_hidden->set('john');
        $this->assertEquals('john', $resource->_hidden->get());
        $response = $resource->toResponse(new Request);
        $this->assertArrayNotHasKey('_hidden', $response->getData(true)['data']);
    }
}
