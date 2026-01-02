<?php

namespace Seier\Resting\Tests\Support;

use stdClass;
use Seier\Resting\Tests\TestCase;
use Illuminate\Http\JsonResponse;
use Seier\Resting\Tests\Meta\PersonResource;
use Seier\Resting\Support\Laravel\RestingResponse;
use Seier\Resting\Support\Laravel\RestingMiddleware;
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
                    'body' => [[
                        'path' => '',
                        'message' => 'The value was expected to be an object, null received instead.'
                    ]]
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
}