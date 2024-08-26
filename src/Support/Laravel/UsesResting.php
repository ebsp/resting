<?php

namespace Seier\Resting\Support\Laravel;

use Exception;
use ReflectionClass;
use ReflectionMethod;
use Seier\Resting\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Seier\Resting\Support\Transformer;
use Seier\Resting\Support\Resourcable;
use Seier\Resting\Support\BaseTransformer;

trait UsesResting
{

    protected Request $request;

    public function callAction($method, $parameters)
    {
        $this->request = request();

        $result = $this->{$method}(...$this->handleVariadicParameters($method, $parameters));

        if ($result instanceof Collection) {
            $result = $result->all();
        } elseif ($result instanceof Resourcable) {
            $result = $this->transform($result);
        }

        if (is_array($result)) {

            $hasIntegerKey = false;
            foreach (array_keys($result) as $key) {
                if (is_int($key)) {
                    $hasIntegerKey = true;
                    break;
                }
            }

            if ($hasIntegerKey) {
                $result = array_values($result);
            }

            $result = RestingResponse::fromResources($result);
        }

        if ($result instanceof \Symfony\Component\HttpFoundation\Response) {
            return $result;
        }

        if ($result instanceof RestingResponse) {
            return $result;
        }

        if ($result instanceof Resource) {
            return new RestingResponse($result->toResponseArray());
        }

        return $result;
    }

    private function handleVariadicParameters(string $method, array $parameters): array
    {
        $methodReflection = new ReflectionMethod($this, $method);
        $reflectionParameters = [];
        foreach ($methodReflection->getParameters() as $reflectionParameter) {
            $reflectionParameters[$reflectionParameter->getName()] = $reflectionParameter;
        }

        $values = [];

        foreach ($parameters as $parameterName => $parameterValue) {

            if (is_array($parameterValue)) {
                if (array_key_exists($parameterName, $reflectionParameters)) {
                    $reflectionParameter = $reflectionParameters[$parameterName];
                    if ($reflectionParameter->isVariadic()) {
                        $type = $reflectionParameters[$parameterName]->getType();
                        if ($type instanceof \ReflectionNamedType) {
                            $resourceName = $type->getName();
                            if ((new ReflectionClass($resourceName))->isSubclassOf(Resource::class)) {
                                array_pop($values);
                                $values = array_merge($values, $parameterValue);
                                continue;
                            }
                        }
                    }
                }
            }

            $values[] = $parameterValue;
        }

        return $values;
    }

    public function transform($data, Transformer $transformer = null)
    {
        if (!$transformer) {
            $transformer = new BaseTransformer;
        }

        if ($data instanceof Collection) {
            return ResourceCollection::fromCollection($data, $transformer);
        }

        if ($data instanceof Resourcable) {
            return $transformer($data);
        }

        throw new Exception;
    }
}
