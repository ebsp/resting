<?php

namespace Seier\Resting\Tests\Support;

use Illuminate\Http\Request;
use Seier\Resting\Tests\TestCase;
use Seier\Resting\Exceptions\EmptyJsonException;
use Seier\Resting\Support\Laravel\RestingMiddleware;

class RestingMiddlewareTest extends TestCase
{
    /**
     * Middleware testing can be tricky when not rendering is inside
     * the wanted environment; This overwrite makes it possible to do
     * basic testing without having to run magic/pseudo requests.
     */
    public function setUp(): void
    {
        $this->middleware = new class extends RestingMiddleware
        {
            public  ?Request $request;

            public function __construct()
            {
                $this->request = new class extends Request
                {
                    public $content;
                    public $method = 'GET';

                    public function expectsJson()
                    {
                        return true;
                    }
                };
            }

            public function validateIsJsonOverwrite()
            {
                return $this->validateIsJsonBody();
            }
        };
    }

    public function testGetAllowsEmptyBody()
    {
        $this->middleware->request->content = '';

        $response = $this->middleware->validateIsJsonOverwrite();

        $this->assertTrue($response);
    }

    public function testPostDisallowsEmptyBody()
    {
        $this->middleware->request->content = '';
        $this->middleware->request->method = 'POST';

        $this->expectException(EmptyJsonException::class);

        $this->middleware->validateIsJsonOverwrite();
    }

    public function testPostAllowsEmptyBodyWithEmptyParameters()
    {
        $this->middleware->request->content = '{}';
        $this->middleware->request->method = 'POST';

        $response = $this->middleware->validateIsJsonOverwrite();

        $this->assertTrue($response);
    }

    public function testPutDisallowsEmptyBody()
    {
        $this->middleware->request->content = '';
        $this->middleware->request->method = 'PUT';

        $this->expectException(EmptyJsonException::class);

        $this->middleware->validateIsJsonOverwrite();
    }

    public function testPutAllowsEmptyBodyWithEmptyParameters()
    {
        $this->middleware->request->content = '{}';
        $this->middleware->request->method = 'POST';

        $response = $this->middleware->validateIsJsonOverwrite();

        $this->assertTrue($response);
    }

    public function testDeleteDisallowsEmptyBody()
    {
        $this->middleware->request->content = '';
        $this->middleware->request->method = 'DELETE';

        $this->expectException(EmptyJsonException::class);

        $this->middleware->validateIsJsonOverwrite();
    }

    public function testDeleteAllowsEmptyBodyWithEmptyParameters()
    {
        $this->middleware->request->content = '{}';
        $this->middleware->request->method = 'POST';

        $response = $this->middleware->validateIsJsonOverwrite();

        $this->assertTrue($response);
    }

    public function testPatchDisallowsEmptyBody()
    {
        $this->middleware->request->content = '';
        $this->middleware->request->method = 'PATCH';

        $this->expectException(EmptyJsonException::class);

        $this->middleware->validateIsJsonOverwrite();
    }

    public function testPatchAllowsEmptyBodyWithEmptyParameters()
    {
        $this->middleware->request->content = '{}';
        $this->middleware->request->method = 'POST';

        $response = $this->middleware->validateIsJsonOverwrite();

        $this->assertTrue($response);
    }
}
