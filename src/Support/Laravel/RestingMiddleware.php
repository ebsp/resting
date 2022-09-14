<?php

namespace Seier\Resting\Support\Laravel;

use Closure;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionUnionType;
use Seier\Resting\Query;
use Seier\Resting\Params;
use Seier\Resting\Resource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Seier\Resting\ClosureResourceFactory;
use Seier\Resting\Exceptions\EmptyJsonException;
use Seier\Resting\Marshaller\ResourceMarshaller;
use Seier\Resting\Exceptions\InvalidJsonException;
use Seier\Resting\Marshaller\ResourceMarshallerResult;
use Seier\Resting\Exceptions\RestingDefinitionException;

class RestingMiddleware
{

    protected ?Request $request;
    protected array $bodyErrors = [];
    protected array $queryErrors = [];
    protected array $paramErrors = [];

    public function handle(Request $request, Closure $next)
    {
        $this->request = $request;

        // validate that we received json
        $this->validateIsJsonBody();

        // clear all parameters for the route, so we can create our own
        $parameters = $this->clearRouteParameters();

        // fills the route parameters array with resources and inputs from the request
        $this->createRouteParameters($parameters);

        // when the marshalling caused validation errors, respond with 422
        if ($this->hasValidationErrors()) {
            return $this->respondWithValidationErrors();
        }

        return $next($request);
    }

    protected function clearRouteParameters(): array
    {
        $parameters = [];
        $route = $this->request->route();
        foreach ($route->parameterNames() as $parameterName) {
            $parameters[$parameterName] = $route->parameter($parameterName);
            $this->request->route()->forgetParameter($parameterName);
        }

        return $parameters;
    }

    private function createRouteParameters(array $originalParameters)
    {
        $route = $this->request->route();
        foreach ($route->signatureParameters() as $parameter) {

            $parameterName = $parameter->getName();
            $parameterType = $parameter->getType();

            // we cannot handle parameters with union types
            if ($parameterType instanceof ReflectionUnionType) {
                throw RestingDefinitionException::cannotResolveUnionParameter($route, $parameter);
            }

            // when the parameter type is a builtin,
            // we assume the user wants to access input values like query and path parameters
            if (!$parameterType || $parameterType->isBuiltin()) {
                $parameterValue = array_key_exists($parameterName, $originalParameters)
                    ? $originalParameters[$parameterName]
                    : $this->request->query($parameterName);

                $this->request->route()->setParameter($parameter->getName(), $parameterValue);
                continue;
            }

            if (!$parameterType instanceof ReflectionNamedType) {
                throw RestingDefinitionException::cannotResolveParameter($route, $parameter);
            }

            $reflectionClass = new ReflectionClass($parameterType->getName());
            if (!$reflectionClass->isSubclassOf(Resource::class)) {
                continue;
            }

            if (!$reflectionClass->isInstantiable()) {
                throw RestingDefinitionException::resourceNotInstantiable($route, $reflectionClass);
            }

            $value = $this->resolveParameter(
                $reflectionClass,
                $parameter->allowsNull(),
                $parameter->isVariadic(),
            );

            $this->request->route()->setParameter($parameterName, $value);
        }
    }

    protected function resolveParameter(ReflectionClass $resourceClass, bool $nullable, bool $isVariadic = false)
    {
        $resourceName = $resourceClass->getName();

        if (!$resourceClass->isSubclassOf(Resource::class)) {
            return null;
        }

        if ($resourceClass->isSubclassOf(Query::class)) {
            return $this->resolveQuery($resourceName);
        }

        if ($resourceClass->isSubclassOf(Params::class)) {
            return $this->resolveParam($resourceName);
        }

        return $this->resolveResource($resourceName, $nullable, $isVariadic);
    }

    protected function resolveParam(string $resourceName)
    {
        $marshaller = new ResourceMarshaller();
        $marshaller->isStringBased();
        $factory = ClosureResourceFactory::from($resourceName);
        $result = $marshaller->marshalResource(
            $factory,
            $this->request->route()->originalParameters(),
        );

        $this->appendErrors($result, $this->paramErrors);

        return $result->getValue();
    }

    protected function resolveQuery(string $resourceName): Resource
    {
        $marshaller = new ResourceMarshaller();
        $marshaller->isStringBased();
        $factory = ClosureResourceFactory::from($resourceName);
        $result = $marshaller->marshalResource(
            $factory,
            $this->request->query->all(),
        );

        $this->appendErrors($result, $this->queryErrors);

        return $result->getValue();
    }

    protected function resolveResource(string $resourceName, bool $nullable, bool $isVariadic = false)
    {
        $content = json_decode($this->request->getContent(), true);

        $marshaller = new ResourceMarshaller();
        $factory = ClosureResourceFactory::from($resourceName);
        $result = $isVariadic
            ? $marshaller->marshalResources($factory, $content)
            : ($nullable ? $marshaller->marshalNullableResource($factory, $content) : $marshaller->marshalResource($factory, $content));

        $this->appendErrors($result, $this->bodyErrors);

        return $result->getValue();
    }

    protected function validateIsJsonBody(): bool
    {
        $body = $this->request->getContent();
        if (
            !$this->request->expectsJson()
            || $this->request->getMethod() === 'GET' && empty($body)
        ) {
            return true;
        }

        if (empty($body)) {
            throw new EmptyJsonException();
        }

        @json_decode($body);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidJsonException();
        }

        return true;
    }

    private function appendErrors(ResourceMarshallerResult $resourceMarshallerResult, array &$destination)
    {
        foreach ($resourceMarshallerResult->getErrors() as $pair) {
            $destination[] = $pair;
        }
    }

    private function hasValidationErrors(): bool
    {
        return (
            !empty($this->bodyErrors) ||
            !empty($this->queryErrors) ||
            !empty($this->paramErrors)
        );
    }

    private function respondWithValidationErrors(): JsonResponse
    {
        $message = 'One or more errors prevented the request from being fulfilled.';
        $body = $this->createErrorList($this->bodyErrors);
        $query = $this->createErrorList($this->queryErrors);
        $param = $this->createErrorList($this->paramErrors);
        $errors = compact('body', 'query', 'param');
        $content = compact('message', 'errors');

        return response()->json($content, 422);
    }

    private function createErrorList(array $errors): array
    {
        $result = [];
        foreach ($errors as $validationError) {
            $result[] = [
                'path' => $validationError->getPath(),
                'message' => $validationError->getMessage(),
            ];
        }

        return $result;
    }
}
