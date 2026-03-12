<?php

namespace Seier\Resting\Tests\Support;

use stdClass;
use Seier\Resting\Tests\TestCase;
use Illuminate\Http\JsonResponse;
use Seier\Resting\Tests\Meta\PersonQuery;
use Seier\Resting\Tests\Meta\PersonParams;
use Seier\Resting\Tests\Meta\PersonResource;
use Seier\Resting\Tests\Meta\CarbonPeriodQuery;
use Seier\Resting\Support\Laravel\RestingResponse;
use Seier\Resting\Support\Laravel\RestingMiddleware;
use Seier\Resting\Tests\Meta\NotRequiredPersonResource;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

class LaravelIntegrationTest extends TestCase
{
    use ArraySubsetAsserts;

    public function setUp(): void
    {
        parent::setUp();

        $this->instance = new RestingMiddleware();
    }

    public function testEmptyResourceIsReturnedCorrectly()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['GET'],
            action: fn (): PersonResource => (new PersonResource)->only(),
        );

        $harnessRun = $harness->request(content: '');

        $this->assertInstanceOf(RestingResponse::class, $response = $harnessRun->getResponse());
        $this->assertArrayHasKey('data', $data = $response->toArray());
        $this->assertTrue($data['data'] instanceof stdClass);
        $this->assertSame(
            (array)new stdClass,
            (array)$data['data']
        );
    }

    public function testWhenNullIsProvidedWhenResourceIsExpected()
    {
        $response = new RestingResponse(data: []);
        $harness = new LaravelIntegrationTestHarness(
            methods: ['POST'],
            action: fn (PersonResource $r) => $response,
        );

        $harnessRun = $harness->request(content: 'null');

        $this->assertFalse($harnessRun->wasActionCalled());
        $this->assertInstanceOf(JsonResponse::class, $response = $harnessRun->getResponse());

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArraySubset(
            [
                'message' => 'One or more errors prevented the request from being fulfilled.',
                'errors' => [
                    'body' => [
                        [
                            'path' => 'name',
                            'message' => 'Value is required, but was not received.'
                        ],
                        [
                            'path' => 'age',
                            'message' => 'Value is required, but was not received.'
                        ]
                    ]
                ]
            ],
            json_decode($response->getContent(), JSON_OBJECT_AS_ARRAY)
        );
    }

    public function testWhenNullIsProvidedWhenArrayOfResourcesIsExpected()
    {
        $response = new RestingResponse(data: []);
        $harness = new LaravelIntegrationTestHarness(
            methods: ['POST'],
            action: fn (PersonResource ...$rs) => $response,
        );

        $harnessRun = $harness->request(content: 'null');

        $this->assertFalse($harnessRun->wasActionCalled());
        $this->assertInstanceOf(JsonResponse::class, $response = $harnessRun->getResponse());

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArraySubset(
            [
                'message' => 'One or more errors prevented the request from being fulfilled.',
                'errors' => [
                    'body' => [[
                        'path' => '',
                        'message' => 'The value was expected to be an array, null received instead.'
                    ]]
                ]
            ],
            json_decode($response->getContent(), JSON_OBJECT_AS_ARRAY)
        );
    }

    public function testCanProvideNullToNullableResourceThroughBody()
    {
        $response = new RestingResponse(data: []);
        $harness = new LaravelIntegrationTestHarness(
            methods: ['POST'],
            action: function (?PersonResource $r) use ($response) {
                $this->assertNull($r);
                return $response;
            },
        );

        $harnessRun = $harness->request(content: 'null');

        $this->assertTrue($harnessRun->wasActionCalled());
        $this->assertInstanceOf(RestingResponse::class, $response = $harnessRun->getResponse());
        $this->assertSame(['data' => []], $response->toArray());
    }

    public function testCanParseContentIntoResource()
    {
        $name = $this->faker->uuid();
        $age = 18;

        $harness = new LaravelIntegrationTestHarness(
            methods: ['POST'],
            action: fn (PersonResource $r) => $r,
        );

        $harnessRun = $harness->request(content: json_encode([
            'name' => $name,
            'age' => $age,
        ]));

        $this->assertTrue($harnessRun->wasActionCalled());
        $this->assertCount(1, $harnessRun->getActionCallArguments());

        $this->assertInstanceOf(PersonResource::class, $person = $harnessRun->getActionCallArguments()[0]);
        $this->assertSame($name, $person->name->get());
        $this->assertSame($age, $person->age->get());
    }

    public function testCanParseQueryContentIntoResource()
    {
        $name = $this->faker->uuid();
        $age = 18;

        $harness = new LaravelIntegrationTestHarness(
            methods: ['POST'],
            action: fn (PersonQuery $q) => $q,
        );

        $harnessRun = $harness->request(query: [
            'name' => $name,
            'age' => $age,
        ]);

        $this->assertTrue($harnessRun->wasActionCalled());
        $this->assertCount(1, $harnessRun->getActionCallArguments());

        $this->assertInstanceOf(PersonQuery::class, $person = $harnessRun->getActionCallArguments()[0]);
        $this->assertSame($name, $person->name->get());
        $this->assertSame($age, $person->age->get());
    }

    public function testCanParseParamContentIntoResource()
    {
        $name = $this->faker->uuid();

        $harness = new LaravelIntegrationTestHarness(
            methods: ['POST'],
            action: fn (PersonParams $p) => $p,
            path: '/create-person/{name}'
        );

        $harnessRun = $harness->request(url: "/create-person/$name");

        $this->assertTrue($harnessRun->wasActionCalled());
        $this->assertCount(1, $harnessRun->getActionCallArguments());

        $this->assertInstanceOf(PersonParams::class, $person = $harnessRun->getActionCallArguments()[0]);
        $this->assertSame($name, $person->name->get());
        $this->assertNull($person->age->get());
    }

    public function testWhenContentNotProvidedAndNothingExpected()
    {
        $name = $this->faker->uuid();

        $harness = new LaravelIntegrationTestHarness(
            methods: ['GET'],
            action: fn (PersonParams $p) => $p,
            path: '/search/{name}'
        );

        $harnessRun = $harness->request(url: "/search/$name", content: null);

        $this->assertTrue($harnessRun->wasActionCalled());

        $this->assertInstanceOf(PersonParams::class, $person = $harnessRun->getActionCallArguments()[0]);
        $this->assertSame($name, $person->name->get());
    }

    public function testContentCanBeNullWhenThereAreOnlyNonRequiredFields()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['GET'],
            action: fn (NotRequiredPersonResource $p) => $p,
            path: '/search'
        );

        $harnessRun = $harness->request(url: "/search", content: null);

        $this->assertTrue($harnessRun->wasActionCalled());

        $this->assertInstanceOf(NotRequiredPersonResource::class, $person = $harnessRun->getActionCallArguments()[0]);
        $this->assertNull($person->name->get());
        $this->assertNull($person->age->get());
    }

    public function testWhenExpectingOneResourceButProvidedArrayOfObjects()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['POST'], 
            action: fn (PersonResource $p) => $p,
        );

        $harnessRun = $harness->request(content: json_encode([
            ['name' => $this->faker->uuid(), 'age' => 1],
        ]));

        $this->assertFalse($harnessRun->wasActionCalled());

        $this->assertInstanceOf(JsonResponse::class, $response = $harnessRun->getResponse());
        $this->assertStringContainsString('The value was expected to be an object, array [object] received instead.', $response->getContent());
    }

    public function testWhenExpectingCarbonPeriodFieldInQuery()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['POST'],
            action: fn (CarbonPeriodQuery $p) => $p,
            path: '/search'
        );

        $harnessRun = $harness->request(url: '/search', query: ['period' => '2025-01-01,2025-01-01']);

        $this->assertTrue($harnessRun->wasActionCalled());
        $this->assertInstanceOf(RestingResponse::class, $harnessRun->getResponse());
        $this->assertCount(1, $harnessRun->getActionCallArguments());
        $this->assertInstanceOf(CarbonPeriodQuery::class, $query = $harnessRun->getActionCallArguments()[0]);
        $this->assertSame('2025-01-01', $query->period->start()->toDateString());
        $this->assertSame('2025-01-01', $query->period->end()->toDateString());
    }
}