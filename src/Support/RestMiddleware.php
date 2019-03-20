<?php

namespace Seier\Resting\Support;

use Closure;
use ReflectionClass;
use Seier\Resting\Query;
use Seier\Resting\Params;
use Seier\Resting\Resource;
use Illuminate\Http\Request;
use Seier\Resting\Exceptions\InvalidJsonException;

class RestMiddleware
{
    /** @var Request */
    protected $request;

    public function handle(Request $request, Closure $next)
    {
        $this->request = $request;

        $this->validateJsonBody();

        foreach ($request->route()->signatureParameters() as $parameter) {
            /** @var \ReflectionParameter $parameter */
            $type = $parameter->getType();
            
            if (! $type) {
                continue;
            }

            $reflectionClass = new ReflectionClass($type->getName());

            if ($reflectionClass->isInstantiable()) {
                $value = $this->resolveParameter($reflectionClass, $parameter->isVariadic());

                if ($parameter->isVariadic() && count($value)) {
                    $request->route()->setParameter($parameter->getName(), $value[0]);

                    foreach (array_slice($value, 1) as $v) {
                        $request->route()->pushParameter($v);
                    }
                } elseif (! $parameter->isVariadic() && is_array($value) && isset($value[0])) {
                    $request->route()->setParameter($parameter->getName(), $value[0]);
                } elseif (! is_array($value)) {
                    $request->route()->setParameter($parameter->getName(), $value);
                }
            }
        }

        return $next($request);
    }

    protected function resolveParameter(ReflectionClass $_class, $isVariadic = false)
    {
        if (! $_class->isSubclassOf(Resource::class)) {
            return null;
        }

        if ($_class->isSubclassOf(Query::class)) {
            return $this->resolveQuery($_class->getName(), $this->request);
        }

        if ($_class->isSubclassOf(Params::class)) {
            return $this->resolveParam($_class->getName(), $this->request);
        }

        $input = $this->request->all();
        $value = [];

        if ($isVariadic) {
            foreach ($this->request->json('data', $this->request->json()) as $values) {
                $value[] = $this->resolveResource($_class->getName(), $values, true);
            }
        } else {
            $value[] = $this->resolveResource($_class->getName(), $this->request->all());
        }

        return $value;
    }

    protected function resolveParam($_class, $values)
    {
        return $this->resolveQuery($_class, $values);
    }

    protected function resolveQuery($_class, $values)
    {
        return $this->finalizeInstance(
            $_class::fromRequest($values)
        );
    }

    protected function resolveResource($_class, $values, $multiple = false)
    {
        return $this->finalizeInstance(
            $_class::fromArray($values, false)->setRequest($this->request), $multiple
        );
    }

    public function finalizeInstance(Resource $resource, $multiple = false)
    {
        $resource->prepare();

        if ($multiple) {
            $this->request->_envelopedResource = true;

            foreach ($resource->validation($this->request) as $key => $rule) {
                $this->request->_validation = array_merge($this->request->_validation ?: [], [
                    'data.*.' . $key => $rule
                ]);
            }
        } else {
            $this->request->_validation = array_merge(
                $this->request->_validation ?? [],
                $resource->validation($this->request)
            );
        }

        return $resource->flatten();
    }

    protected function validateJsonBody()
    {
        $body = $this->request->getContent();

        if (! $this->request->expectsJson() || empty($body)) {
            return true;
        }

        @json_decode($body);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidJsonException('Invalid json');
        }

        return true;
    }
}
