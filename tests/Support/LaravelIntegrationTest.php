<?php

namespace Seier\Resting\Tests\Support;

use stdClass;
use Seier\Resting\Tests\TestCase;
use Illuminate\Http\JsonResponse;
use Seier\Resting\Tests\Meta\PetResource;
use Seier\Resting\Tests\Meta\PersonQuery;
use Seier\Resting\Tests\Meta\ClassResource;
use Seier\Resting\Tests\Meta\ScalarArraysQuery;
use Seier\Resting\Tests\Meta\ArrayResourceFieldsResource;
use Seier\Resting\Tests\Meta\PersonParams;
use Seier\Resting\Tests\Meta\PersonResource;
use Seier\Resting\Tests\Meta\CarbonPeriodQuery;
use Seier\Resting\Support\Laravel\RestingResponse;
use Seier\Resting\Support\Laravel\RestingMiddleware;
use Seier\Resting\Tests\Meta\NotRequiredPersonResource;

class LaravelIntegrationTest extends TestCase
{
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

    public function testScalarPathParameterIsCoercedToDeclaredType()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['GET'],
            action: fn (int $id) => new RestingResponse(data: []),
            path: '/items/{id}',
        );

        $harnessRun = $harness->request(url: '/items/5');

        $this->assertTrue($harnessRun->wasActionCalled());
        $this->assertSame(5, $harnessRun->getActionCallArguments()[0]);
    }

    public function testScalarQueryParameterIsCoercedToDeclaredType()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['GET'],
            action: fn (bool $active) => new RestingResponse(data: []),
            path: '/search',
        );

        $harnessRun = $harness->request(url: '/search', query: ['active' => 'true']);

        $this->assertTrue($harnessRun->wasActionCalled());
        $this->assertSame(true, $harnessRun->getActionCallArguments()[0]);
    }

    public function testInvalidScalarPathParameterProducesParamValidationError()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['GET'],
            action: fn (int $id) => $id,
            path: '/items/{id}',
        );

        $harnessRun = $harness->request(url: '/items/not-a-number');

        $this->assertFalse($harnessRun->wasActionCalled());
        $this->assertInstanceOf(JsonResponse::class, $response = $harnessRun->getResponse());
        $this->assertSame(422, $response->getStatusCode());
        $this->assertArraySubset(
            [
                'message' => 'One or more errors prevented the request from being fulfilled.',
                'errors' => [
                    'param' => [[
                        'path' => 'id',
                        'message' => "Expected value that can be parsed as int ([0-9]+), received string ('not-a-number') instead.",
                    ]],
                ],
            ],
            json_decode($response->getContent(), JSON_OBJECT_AS_ARRAY)
        );
    }

    public function testInvalidScalarQueryParameterProducesQueryValidationError()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['GET'],
            action: fn (int $id) => $id,
            path: '/search',
        );

        $harnessRun = $harness->request(url: '/search', query: ['id' => 'not-a-number']);

        $this->assertFalse($harnessRun->wasActionCalled());
        $this->assertInstanceOf(JsonResponse::class, $response = $harnessRun->getResponse());
        $this->assertSame(422, $response->getStatusCode());
        $this->assertArraySubset(
            [
                'errors' => [
                    'query' => [[
                        'path' => 'id',
                        'message' => "Expected value that can be parsed as int ([0-9]+), received string ('not-a-number') instead.",
                    ]],
                ],
            ],
            json_decode($response->getContent(), JSON_OBJECT_AS_ARRAY)
        );
    }

    public function testMissingRequiredScalarParameterProducesValidationError()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['GET'],
            action: fn (int $id) => $id,
            path: '/search',
        );

        $harnessRun = $harness->request(url: '/search');

        $this->assertFalse($harnessRun->wasActionCalled());
        $this->assertInstanceOf(JsonResponse::class, $response = $harnessRun->getResponse());
        $this->assertSame(422, $response->getStatusCode());
        $this->assertArraySubset(
            [
                'errors' => [
                    'query' => [[
                        'path' => 'id',
                        'message' => 'Value is required, but was not received.',
                    ]],
                ],
            ],
            json_decode($response->getContent(), JSON_OBJECT_AS_ARRAY)
        );
    }

    public function testNullableScalarParameterIsNullWhenMissing()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['GET'],
            action: fn (?int $id) => new RestingResponse(data: []),
            path: '/search',
        );

        $harnessRun = $harness->request(url: '/search');

        $this->assertTrue($harnessRun->wasActionCalled());
        $this->assertNull($harnessRun->getActionCallArguments()[0]);
    }

    public function testScalarParameterDefaultIsUsedWhenMissing()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['GET'],
            action: fn (int $page = 1) => new RestingResponse(data: []),
            path: '/search',
        );

        $harnessRun = $harness->request(url: '/search');

        $this->assertTrue($harnessRun->wasActionCalled());
        $this->assertSame(1, $harnessRun->getActionCallArguments()[0]);
    }

    private function assertBodyValidationErrors(LaravelIntegrationTestHarnessRunResult $run, array $expectedErrors): void
    {
        $this->assertFalse($run->wasActionCalled());
        $this->assertInstanceOf(JsonResponse::class, $response = $run->getResponse());
        $this->assertSame(422, $response->getStatusCode());
        $this->assertArraySubset(
            ['errors' => ['body' => $expectedErrors]],
            json_decode($response->getContent(), JSON_OBJECT_AS_ARRAY)
        );
    }

    public function testWhenExpectingResourceObjectButProvidedInt()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['POST'],
            action: fn (PersonResource $r) => new RestingResponse(data: []),
        );

        $this->assertBodyValidationErrors($harness->request(content: json_encode(5)), [
            ['path' => '', 'message' => 'The value was expected to be an object, number (5) received instead.'],
        ]);
    }

    public function testWhenExpectingResourceObjectButProvidedString()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['POST'],
            action: fn (PersonResource $r) => new RestingResponse(data: []),
        );

        $this->assertBodyValidationErrors($harness->request(content: json_encode('hello')), [
            ['path' => '', 'message' => "The value was expected to be an object, string ('hello') received instead."],
        ]);
    }

    public function testWhenExpectingResourceObjectButProvidedBool()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['POST'],
            action: fn (PersonResource $r) => new RestingResponse(data: []),
        );

        $this->assertBodyValidationErrors($harness->request(content: json_encode(true)), [
            ['path' => '', 'message' => 'The value was expected to be an object, bool (true) received instead.'],
        ]);
    }

    public function testWhenExpectingArrayOfResourcesButProvidedObject()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['POST'],
            action: fn (PersonResource ...$rs) => new RestingResponse(data: []),
        );

        $this->assertBodyValidationErrors($harness->request(content: json_encode(['name' => 'x', 'age' => 1])), [
            ['path' => '', 'message' => 'The value was expected to be an array, object received instead.'],
        ]);
    }

    public function testWhenExpectingArrayOfResourcesButProvidedInt()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['POST'],
            action: fn (PersonResource ...$rs) => new RestingResponse(data: []),
        );

        $this->assertBodyValidationErrors($harness->request(content: json_encode(5)), [
            ['path' => '', 'message' => 'The value was expected to be an array, number (5) received instead.'],
        ]);
    }

    public function testWhenExpectingArrayOfResourcesButProvidedString()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['POST'],
            action: fn (PersonResource ...$rs) => new RestingResponse(data: []),
        );

        $this->assertBodyValidationErrors($harness->request(content: json_encode('hello')), [
            ['path' => '', 'message' => "The value was expected to be an array, string ('hello') received instead."],
        ]);
    }

    public function testWhenExpectingResourceArrayFieldButProvidedInt()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['POST'],
            action: fn (ClassResource $r) => new RestingResponse(data: []),
        );

        $this->assertBodyValidationErrors($harness->request(content: json_encode(['grade' => 1, 'students' => 5])), [
            ['path' => 'students', 'message' => 'The value was expected to be an array, number (5) received instead.'],
        ]);
    }

    public function testWhenExpectingResourceArrayFieldButProvidedString()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['POST'],
            action: fn (ClassResource $r) => new RestingResponse(data: []),
        );

        $this->assertBodyValidationErrors($harness->request(content: json_encode(['grade' => 1, 'students' => 'x'])), [
            ['path' => 'students', 'message' => "The value was expected to be an array, string ('x') received instead."],
        ]);
    }

    public function testWhenExpectingArrayOfObjectsButProvidedArrayOfInts()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['POST'],
            action: fn (ClassResource $r) => new RestingResponse(data: []),
        );

        $this->assertBodyValidationErrors($harness->request(content: json_encode(['grade' => 1, 'students' => [1, 2, 3]])), [
            ['path' => 'students.0', 'message' => 'The value was expected to be an object, number (1) received instead.'],
            ['path' => 'students.1', 'message' => 'The value was expected to be an object, number (2) received instead.'],
            ['path' => 'students.2', 'message' => 'The value was expected to be an object, number (3) received instead.'],
        ]);
    }

    public function testWhenExpectingArrayOfObjectsButProvidedArrayOfStrings()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['POST'],
            action: fn (ClassResource $r) => new RestingResponse(data: []),
        );

        $this->assertBodyValidationErrors($harness->request(content: json_encode(['grade' => 1, 'students' => ['a', 'b']])), [
            ['path' => 'students.0', 'message' => "The value was expected to be an object, string ('a') received instead."],
            ['path' => 'students.1', 'message' => "The value was expected to be an object, string ('b') received instead."],
        ]);
    }

    public function testWhenExpectingArrayOfObjectsButProvidedArrayOfBools()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['POST'],
            action: fn (ClassResource $r) => new RestingResponse(data: []),
        );

        $this->assertBodyValidationErrors($harness->request(content: json_encode(['grade' => 1, 'students' => [true]])), [
            ['path' => 'students.0', 'message' => 'The value was expected to be an object, bool (true) received instead.'],
        ]);
    }

    public function testWhenExpectingNestedResourceButProvidedInt()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['POST'],
            action: fn (PetResource $r) => new RestingResponse(data: []),
        );

        $this->assertBodyValidationErrors($harness->request(content: json_encode(['name' => 'x', 'owner' => 5])), [
            ['path' => 'owner', 'message' => 'The value was expected to be an object, number (5) received instead.'],
        ]);
    }

    public function testWhenExpectingNestedResourceButProvidedString()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['POST'],
            action: fn (PetResource $r) => new RestingResponse(data: []),
        );

        $this->assertBodyValidationErrors($harness->request(content: json_encode(['name' => 'x', 'owner' => 'bob'])), [
            ['path' => 'owner', 'message' => "The value was expected to be an object, string ('bob') received instead."],
        ]);
    }

    public function testWhenExpectingIntFieldButProvidedArray()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['POST'],
            action: fn (PersonResource $r) => new RestingResponse(data: []),
        );

        $this->assertBodyValidationErrors($harness->request(content: json_encode(['name' => 'x', 'age' => [1, 2]])), [
            ['path' => 'age', 'message' => 'The value is required to be an integer, array [number (1), number (2)] received instead.'],
        ]);
    }

    public function testWhenExpectingStringFieldButProvidedArray()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['POST'],
            action: fn (PersonResource $r) => new RestingResponse(data: []),
        );

        $this->assertBodyValidationErrors($harness->request(content: json_encode(['name' => [1], 'age' => 5])), [
            ['path' => 'name', 'message' => 'The value is required to be a string, array [number (1)] received instead.'],
        ]);
    }

    public function testResourceArrayFieldAcceptsNullElementWhenAllowed()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['POST'],
            action: fn (ArrayResourceFieldsResource $r) => new RestingResponse(data: []),
        );

        $harnessRun = $harness->request(content: json_encode([
            'persons' => [],
            'nullable_persons' => [null],
        ]));

        $this->assertTrue($harnessRun->wasActionCalled());
        $this->assertInstanceOf(ArrayResourceFieldsResource::class, $resource = $harnessRun->getActionCallArguments()[0]);
        $this->assertSame([null], $resource->nullable_persons->get());
    }

    public function testResourceArrayFieldRejectsNullElementWhenNotAllowed()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['POST'],
            action: fn (ArrayResourceFieldsResource $r) => new RestingResponse(data: []),
        );

        $harnessRun = $harness->request(content: json_encode([
            'persons' => [null],
            'nullable_persons' => [],
        ]));

        $this->assertBodyValidationErrors($harnessRun, [
            ['path' => 'persons.0', 'message' => 'Value is not nullable, but null was provided.'],
        ]);
    }

    public function testFloatScalarParameterIsCoerced()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['GET'],
            action: fn (float $ratio) => new RestingResponse(data: []),
            path: '/search',
        );

        $harnessRun = $harness->request(url: '/search', query: ['ratio' => '1.5']);

        $this->assertTrue($harnessRun->wasActionCalled());
        $this->assertSame(1.5, $harnessRun->getActionCallArguments()[0]);
    }

    public function testInvalidFloatScalarParameterProducesValidationError()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['GET'],
            action: fn (float $ratio) => new RestingResponse(data: []),
            path: '/search',
        );

        $harnessRun = $harness->request(url: '/search', query: ['ratio' => 'abc']);

        $this->assertFalse($harnessRun->wasActionCalled());
        $this->assertInstanceOf(JsonResponse::class, $response = $harnessRun->getResponse());
        $this->assertSame(422, $response->getStatusCode());
        $this->assertArraySubset(
            [
                'errors' => [
                    'query' => [[
                        'path' => 'ratio',
                        'message' => 'Could not parse provided abc into a numeric value.',
                    ]],
                ],
            ],
            json_decode($response->getContent(), JSON_OBJECT_AS_ARRAY)
        );
    }

    public function testInvalidBoolScalarParameterProducesValidationError()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['GET'],
            action: fn (bool $active) => new RestingResponse(data: []),
            path: '/search',
        );

        $harnessRun = $harness->request(url: '/search', query: ['active' => 'yes']);

        $this->assertFalse($harnessRun->wasActionCalled());
        $this->assertInstanceOf(JsonResponse::class, $response = $harnessRun->getResponse());
        $this->assertSame(422, $response->getStatusCode());
        $this->assertArraySubset(
            [
                'errors' => [
                    'query' => [[
                        'path' => 'active',
                        'message' => "Expected one of 1,0,true,false, received string ('yes') instead.",
                    ]],
                ],
            ],
            json_decode($response->getContent(), JSON_OBJECT_AS_ARRAY)
        );
    }

    public function testStringScalarParameterIsPassedThroughUnchanged()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['GET'],
            action: fn (string $name) => new RestingResponse(data: []),
            path: '/search',
        );

        $harnessRun = $harness->request(url: '/search', query: ['name' => 'hello']);

        $this->assertTrue($harnessRun->wasActionCalled());
        $this->assertSame('hello', $harnessRun->getActionCallArguments()[0]);
    }

    public function testUntypedParameterIsPassedThroughUnchanged()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['GET'],
            action: fn ($raw) => new RestingResponse(data: []),
            path: '/search',
        );

        $harnessRun = $harness->request(url: '/search', query: ['raw' => 'anything']);

        $this->assertTrue($harnessRun->wasActionCalled());
        $this->assertSame('anything', $harnessRun->getActionCallArguments()[0]);
    }

    public function testArrayQueryParameterOfIntegersIsParsed()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['GET'],
            action: fn (ScalarArraysQuery $q) => $q,
            path: '/search',
        );

        $harnessRun = $harness->request(url: '/search', query: ['ints' => ['1', '2', '3']]);

        $this->assertTrue($harnessRun->wasActionCalled());
        $this->assertInstanceOf(ScalarArraysQuery::class, $query = $harnessRun->getActionCallArguments()[0]);
        $this->assertSame([1, 2, 3], $query->ints->get());
    }

    public function testArrayQueryParameterOfStringsIsParsed()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['GET'],
            action: fn (ScalarArraysQuery $q) => $q,
            path: '/search',
        );

        $harnessRun = $harness->request(url: '/search', query: ['strings' => ['a', 'b', 'c']]);

        $this->assertTrue($harnessRun->wasActionCalled());
        $this->assertInstanceOf(ScalarArraysQuery::class, $query = $harnessRun->getActionCallArguments()[0]);
        $this->assertSame(['a', 'b', 'c'], $query->strings->get());
    }

    public function testArrayQueryParameterOfBooleansIsParsed()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['GET'],
            action: fn (ScalarArraysQuery $q) => $q,
            path: '/search',
        );

        $harnessRun = $harness->request(url: '/search', query: ['bools' => ['true', '0', '1', 'false']]);

        $this->assertTrue($harnessRun->wasActionCalled());
        $this->assertInstanceOf(ScalarArraysQuery::class, $query = $harnessRun->getActionCallArguments()[0]);
        $this->assertSame([true, false, true, false], $query->bools->get());
    }

    public function testCommaSeparatedArrayQueryParameterIsParsed()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['GET'],
            action: fn (ScalarArraysQuery $q) => $q,
            path: '/search',
        );

        $harnessRun = $harness->request(url: '/search', query: ['ints' => '1,2,3']);

        $this->assertTrue($harnessRun->wasActionCalled());
        $this->assertInstanceOf(ScalarArraysQuery::class, $query = $harnessRun->getActionCallArguments()[0]);
        $this->assertSame([1, 2, 3], $query->ints->get());
    }

    public function testArrayQueryParameterWithInvalidElementProducesValidationError()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['GET'],
            action: fn (ScalarArraysQuery $q) => $q,
            path: '/search',
        );

        $harnessRun = $harness->request(url: '/search', query: ['ints' => ['1', 'x', '3']]);

        $this->assertFalse($harnessRun->wasActionCalled());
        $this->assertInstanceOf(JsonResponse::class, $response = $harnessRun->getResponse());
        $this->assertSame(422, $response->getStatusCode());
        $this->assertArraySubset(
            [
                'errors' => [
                    'query' => [[
                        'path' => 'ints.1',
                        'message' => "Expected value that can be parsed as int ([0-9]+), received string ('x') instead.",
                    ]],
                ],
            ],
            json_decode($response->getContent(), JSON_OBJECT_AS_ARRAY)
        );
    }

    public function testArrayQueryParameterWithEmptyElementProducesValidationError()
    {
        $harness = new LaravelIntegrationTestHarness(
            methods: ['GET'],
            action: fn (ScalarArraysQuery $q) => $q,
            path: '/search',
        );

        // ?ints[]=1&ints[]=
        $harnessRun = $harness->request(url: '/search', query: ['ints' => ['1', '']]);

        $this->assertFalse($harnessRun->wasActionCalled());
        $this->assertInstanceOf(JsonResponse::class, $response = $harnessRun->getResponse());
        $this->assertSame(422, $response->getStatusCode());
        $this->assertArraySubset(
            [
                'errors' => [
                    'query' => [[
                        'path' => 'ints.1',
                        'message' => "Expected value that can be parsed as int ([0-9]+), received string ('') instead.",
                    ]],
                ],
            ],
            json_decode($response->getContent(), JSON_OBJECT_AS_ARRAY)
        );
    }
}