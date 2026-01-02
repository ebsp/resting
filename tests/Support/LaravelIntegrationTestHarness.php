<?php

namespace Seier\Resting\Tests\Support;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use ReflectionFunctionAbstract;
use Seier\Resting\Support\Laravel\UsesResting;
use Seier\Resting\Support\Laravel\RestingResponse;
use Seier\Resting\Support\Laravel\RestingMiddleware;
use Laravel\SerializableClosure\Support\ReflectionClosure;
use Symfony\Component\HttpFoundation\Response;

class LaravelIntegrationTestHarness
{
    use UsesResting;

    private RestingMiddleware $restingMiddleware;
    private string $url;
    private Closure $actionClosure;
    private Route $route;

    protected Request $request;

    private Response|RestingResponse|null $response = null;
    private array|null $actionCallArguments = null;
    private bool $wasActionCalled = false;

    public function __construct(
        array $methods,
        Closure $action,
    )
    {
        $this->restingMiddleware = new RestingMiddleware();
        $this->url = Str::random();
        $this->actionClosure = $action;
        $this->route = new Route(
            methods: $methods,
            uri: $this->url,
            action: $this->actionClosure,
        );
    }

    public function request(string $content): LaravelIntegrationTestHarnessRunResult
    {
        $this->response = null;
        $this->actionCallArguments = null;
        $this->wasActionCalled = false;

        $this->request = new Request(content: $content);
        $this->request->setRouteResolver(fn () => $this->route);
        $this->route->bind($this->request);

        $this->response = $this->restingMiddleware->handle($this->request, function (Request $request) {
            return $this->callAction('controllerMethod', $this->route->parameters());
        });

        return new LaravelIntegrationTestHarnessRunResult(
            request: $this->request,
            response: $this->response,
            wasActionCalled: $this->wasActionCalled,
            actionCallArguments: $this->actionCallArguments,
        );
    }

    public function resolveReflectionFunction(string $methodName): ReflectionFunctionAbstract
    {
        return new ReflectionClosure($this->actionClosure);
    }

    public function controllerMethod(): mixed
    {
        $this->actionCallArguments = func_get_args();
        $this->wasActionCalled = true;

        return ($this->actionClosure)(...$this->actionCallArguments);
    }
}