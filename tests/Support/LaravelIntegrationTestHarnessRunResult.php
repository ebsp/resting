<?php

namespace Seier\Resting\Tests\Support;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Seier\Resting\Support\Laravel\RestingResponse;

class LaravelIntegrationTestHarnessRunResult
{
    private Request $request;
    private Response|RestingResponse $response;
    private bool $wasActionCalled;
    private ?array $actionCallArguments;

    public function __construct(
        Request $request,
        RestingResponse|Response $response,
        bool $wasActionCalled,
        ?array $actionCallArguments
    )
    {
        $this->request = $request;
        $this->response = $response;
        $this->wasActionCalled = $wasActionCalled;
        $this->actionCallArguments = $actionCallArguments;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): RestingResponse|Response
    {
        return $this->response;
    }

    public function wasActionCalled(): bool
    {
        return $this->wasActionCalled;
    }

    public function getActionCallArguments(): ?array
    {
        return $this->actionCallArguments;
    }
}