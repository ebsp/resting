<?php

namespace Seier\Resting\Tests\Support;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use ReflectionFunctionAbstract;
use Seier\Resting\Support\Laravel\UsesResting;
use Symfony\Component\HttpFoundation\Response;
use Seier\Resting\Support\Laravel\RestingResponse;
use Seier\Resting\Support\Laravel\RestingMiddleware;
use Laravel\SerializableClosure\Support\ReflectionClosure;

class LaravelIntegrationTestHarness
{
    use UsesResting;

    private RestingMiddleware $restingMiddleware;
    private string $path;
    private Closure $actionClosure;
    private Route $route;

    protected Request $request;

    private Response|RestingResponse|null $response = null;
    private array|null $actionCallArguments = null;
    private bool $wasActionCalled = false;

    public function __construct(
        array $methods,
        Closure $action,
        ?string $path = null,
    )
    {
        $this->restingMiddleware = new RestingMiddleware();
        $this->path = trim($path ?? Str::random(), '/');
        $this->actionClosure = $action;
        $this->route = new Route(
            methods: $methods,
            uri: $this->path,
            action: $this->actionClosure,
        );
    }

    public function request(?string $url = null, string $content = null, array $query = []): LaravelIntegrationTestHarnessRunResult
    {
        $url ??= $this->path;
        $url = trim($url, '/');

        $this->response = null;
        $this->actionCallArguments = null;
        $this->wasActionCalled = false;

        $server = $this->createFakeServerEnvironments(
            requestPath: $url,
            queryParameters: $query,
        );

        $this->request = new Request(query: $query, server: $server, content: $content);
        $this->request->setRouteResolver(fn () => $this->route);
        $this->route->bind($this->request);
        $this->response = $this->restingMiddleware->handle($this->request, function (Request $request) {
            return $this->callAction([$this, 'controllerMethod'][1], $this->route->parameters());
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

    private function createFakeServerEnvironments(string $requestPath, array $queryParameters): array
    {
        $queryPath = "";
        foreach ($queryParameters as $queryKey => $queryValue) {
            $queryPath .= $queryPath === '' ? '?' : '&';
            $queryPath .= "$queryKey=$queryValue";
        }

        return [
            "DOCUMENT_ROOT" => "/api/public",
            "REMOTE_ADDR" => "127.0.0.1",
            "SERVER_SOFTWARE" => "PHP 8.2.29 Development Server",
            "SERVER_PROTOCOL" => "HTTP/1.1",
            "SERVER_NAME" => "127.0.0.1",
            "SERVER_PORT" => "9000",
            "REQUEST_URI" => "/$requestPath",
            "REQUEST_METHOD" => Arr::random($this->route->methods()),
            "SCRIPT_NAME" => "/index.php",
            "SCRIPT_FILENAME" => "/api/public/index.php",
            "PATH_INFO" => "/$requestPath",
            "PHP_SELF" => "/index.php/$requestPath",
            "HTTP_HOST" => "localhost:9000",
        ];
    }
}