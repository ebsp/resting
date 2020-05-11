<?php


namespace Seier\Resting\Tests\Resources;

use Illuminate\Http\Request;
use Seier\Resting\Fields\ResourceArrayField;
use Seier\Resting\Fields\ResourceField;
use Seier\Resting\Tests\TestCase;

class UnionResourceTest extends TestCase
{

    public function testFromArrayOnUnionSubResource()
    {
        $a = UnionResourceA::fromArray(['discriminator' => 'a', 'a' => 'a_value', 'value' => 'value'])->flatten();

        $this->assertInstanceOf(UnionResourceA::class, $a);
        $this->assertEquals('a_value', $a->a);
        $this->assertEquals('value', $a->value);
    }

    public function testUnionResourceFieldRecognizesResourceA()
    {
        $resourceField = new ResourceField(new UnionResourceBase);
        $resourceField->set(['discriminator' => 'a', 'a' => 'a_value', 'value' => 'value']);

        $get = $resourceField->get();;

        $this->assertInstanceOf(UnionResourceA::class, $get);
        $this->assertEquals('a_value', $get->a);
        $this->assertEquals('value', $get->value);
    }

    public function testUnionResourceFieldRecognizesResourceB()
    {
        $resourceField = new ResourceField(new UnionResourceBase);
        $resourceField->set(['discriminator' => 'b', 'b' => 'b_value', 'value' => 'value']);
        $get = $resourceField->get();

        $this->assertInstanceOf(UnionResourceB::class, $get);
        $this->assertEquals('b_value', $get->b);
        $this->assertEquals('value', $get->value);
    }

    public function testUnionResourceFieldCanContainDiscriminator()
    {
        $resourceField = new ResourceField(new UnionResourceBase);
        $resourceField->set(['discriminator' => 'a', 'a' => 'a_value', 'value' => 'value']);
        $get = $resourceField->get();

        $this->assertInstanceOf(UnionResourceA::class, $get);
        $this->assertEquals('a', $get->discriminator);
    }

    public function testUnionResourceFieldCanStillAcceptNullValues()
    {
        $resourceField = new ResourceField(new UnionResourceBase);
        $resourceField->set(['discriminator' => 'a', 'value' => 'value']);
        $get = $resourceField->get();

        $this->assertInstanceOf(UnionResourceA::class, $get);
        $this->assertEquals('a', $get->discriminator);
        $this->assertEquals('value', $get->value);
        $this->assertNull($get->a);
    }

    public function testUnionResourceValidation()
    {
        $unionResource = new UnionResourceBase();
        $rules = $unionResource->validation(new Request());

        $this->assertArrayHasKey('discriminator', $rules);
        $this->assertNotFalse(array_search('in:a,b', $rules['discriminator']));
        $this->assertNotFalse(array_search('required', $rules['discriminator']));
    }

    public function testUnionResourceValidationWithRequestDiscriminator()
    {
        $request = \Mockery::mock(Request::class);
        $request->makePartial();
        $request->allows('all')->andReturn(['discriminator' => 'a']);
        $request->allows('getMethod')->andReturn('GET');

        $unionResource = new UnionResourceBase();

        $rules = $unionResource->validation($request);

        $this->assertArrayHasKey('discriminator', $rules);
        $this->assertArrayHasKey('value', $rules);
        $this->assertArrayHasKey('a', $rules);

        $this->assertNotFalse(array_search('in:a,b', $rules['discriminator']));
        $this->assertNotFalse(array_search('required', $rules['discriminator']));
    }

    public function testUnionResourceDelegatesWhenDiscriminatorIsSet()
    {
        $methods = [
            'toArray' => [],
            'flatten' => [],
            'values' => [],
            'toResponse' => [new Request()],
            'original' => [],
            'toJson' => [],
            'responseCode' => [new Request()],
            'toResponseArray' => [],
        ];

        $createSubject = function () {
            $unionResource = new UnionResourceBase();
            $unionResource->setPropertiesFromCollection(collect(['discriminator' => 'a', 'a' => 'a_value', 'value' => 'value']));
            return $unionResource;
        };

        foreach ($methods as $method => $arguments) {
            $this->assertEquals(
                $createSubject()->{$method}(...$arguments),
                $createSubject()->get()->{$method}(...$arguments),
            );
        }
    }

    public function testUnionResourceArrayField()
    {
        $resourceArrayField = new ResourceArrayField(new UnionResourceBase());
        $resourceArrayField->set([
            ['discriminator' => 'a', 'a' => 'a_value', 'value' => 'a_value'],
            ['discriminator' => 'b', 'b' => 'b_value', 'value' => 'b_value'],
        ]);

        $get = $resourceArrayField->get();

        $this->assertCount(2, $get);

        $this->assertInstanceOf(UnionResourceA::class, $get[0]);
        $this->assertEquals('a_value', $get[0]->a);
        $this->assertEquals('a_value', $get[0]->value);

        $this->assertInstanceOf(UnionResourceB::class, $get[1]);
        $this->assertEquals('b_value', $get[1]->b);
        $this->assertEquals('b_value', $get[1]->value);
    }

    public function testUnionSubResourceOnArrayField()
    {
        $resourceArrayField = new ResourceArrayField(new UnionResourceA());
        $resourceArrayField->set([
            ['discriminator' => 'a', 'a' => 'a_value', 'value' => 'a_value'],
            ['discriminator' => 'a', 'a' => 'a_value', 'value' => 'a_value'],
        ]);

        $get = $resourceArrayField->get();

        $this->assertCount(2, $get);
        $this->assertInstanceOf(UnionResourceA::class, $get[0]);
        $this->assertInstanceOf(UnionResourceA::class, $get[1]);
    }

    public function testFromRaw()
    {
        $value = [[
            'union' => [
                ['discriminator' => 'a', 'a' => 'a_value', 'value' => 'a_value'],
                ['discriminator' => 'b', 'b' => 'b_value', 'value' => 'b_value'],
            ]
        ]];

        $this->assertEquals($value, UnionParentResource::fromRaw($value)->toArray());
    }
}