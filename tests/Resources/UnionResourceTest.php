<?php


namespace Seier\Resting\Tests\Resources;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Seier\Resting\Fields\ResourceArrayField;
use Seier\Resting\Fields\ResourceField;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\UnionResource;

class UnionResourceTest extends TestCase
{

    protected function unionResourceField()
    {
        return new ResourceField(new UnionResource('discriminator', [
            'a' => new UnionResourceA(),
            'b' => new UnionResourceB(),
        ]));
    }

    protected function unionResourceArrayField()
    {
        return new ResourceArrayField(new UnionResource('discriminator', [
            'a' => new UnionResourceA(),
            'b' => new UnionResourceB(),
        ]));
    }

    public function testValidation()
    {
        $resourceField = $this->unionResourceField();
        $rules = $resourceField->get()->validation(new Request())['discriminator'];
        $this->assertNotFalse(array_search('in:a,b', $rules));
        $this->assertNotFalse(array_search('required', $rules));
    }

    public function testUnionRecognizesResourceA()
    {
        $resourceField = $this->unionResourceField();
        $resourceField->set(['discriminator' => 'a', 'a_specific' => 'a_value', 'value' => 'value']);
        $get = $resourceField->get();

        $this->assertInstanceOf(UnionResourceA::class, $get);
        $this->assertEquals('a_value', $get->a_specific);
        $this->assertEquals('value', $get->value);
    }

    public function testUnionRecognizesResourceB()
    {
        $resourceField = $this->unionResourceField();
        $resourceField->set(['discriminator' => 'b', 'b_specific' => 'b_value', 'value' => 'value']);
        $get = $resourceField->get();

        $this->assertInstanceOf(UnionResourceB::class, $get);
        $this->assertEquals('b_value', $get->b_specific);
        $this->assertEquals('value', $get->value);
    }

    public function testUnionResourceCanContainDiscriminator()
    {
        $resourceField = $this->unionResourceField();
        $resourceField->set(['discriminator' => 'a', 'a_specific' => 'a_value', 'value' => 'value']);
        $get = $resourceField->get();

        $this->assertInstanceOf(UnionResourceA::class, $get);
        $this->assertEquals('a', $get->discriminator);
    }

    public function testUnionResourceArrayField()
    {
        $resourceArrayField = $this->unionResourceArrayField();
        $resourceArrayField->set([
            ['discriminator' => 'a', 'a_specific' => 'a_value', 'value' => 'a_value'],
            ['discriminator' => 'b', 'b_specific' => 'b_value', 'value' => 'b_value'],
        ]);

        $get = $resourceArrayField->get();

        $this->assertCount(2, $get);

        $this->assertInstanceOf(UnionResourceA::class, $get[0]);
        $this->assertEquals('a_value', $get[0]->a_specific);
        $this->assertEquals('a_value', $get[0]->value);

        $this->assertInstanceOf(UnionResourceB::class, $get[1]);
        $this->assertEquals('b_value', $get[1]->b_specific);
        $this->assertEquals('b_value', $get[1]->value);
    }

    public function testFromRaw()
    {
        $value = [[
            'union' => [
                ['discriminator' => 'a', 'a_specific' => 'a_value', 'value' => 'a_value'],
                ['discriminator' => 'b', 'b_specific' => 'b_value', 'value' => 'b_value'],
            ]
        ]];

        $this->assertEquals($value, UnionParentResource::fromRaw($value)->toArray());
    }
}