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

            if (!$type) {
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
                } elseif (!$parameter->isVariadic() && is_array($value) && isset($value[0])) {
                    $request->route()->setParameter($parameter->getName(), $value[0]);
                } elseif (!is_array($value)) {
                    $request->route()->setParameter($parameter->getName(), $value);
                }
            }
        }

        return $next($request);
    }

    protected function resolveParameter(ReflectionClass $_class, $isVariadic = false)
    {
        if (!$_class->isSubclassOf(Resource::class)) {
            return null;
        }

        if ($_class->isSubclassOf(Query::class)) {
            return $this->resolveQuery($_class->getName(), $this->request);
        }

        if ($_class->isSubclassOf(Params::class)) {
            return $this->resolveParam($_class->getName(), $this->request);
        }

        $value = [];

        if ($isVariadic) {
            $data = $this->request->json('data', $this->request->json());
            foreach (!$data->count() || is_array($data->all()[0]) ? $data : [] as $index => $values) {
                $this->request->_arrayBody = true;
                $value[] = $this->resolveResource($_class->getName(), $values, true, $index);
            }
        } else {
            $value[] = $this->resolveResource($_class->getName(), $this->request->all());
        }

        return $value;
    }

    protected function resolveParam($_class, $values)
    {
        return $this->finalizeInstance(
            $_class::fromRequest($values, true), false, 'param'
        );
    }

    protected function resolveQuery($_class, $values)
    {
        return $this->finalizeInstance(
            $_class::fromRequest($values, true), false, 'query'
        );
    }

    protected function resolveResource($_class, $values, $multiple = false, int $index = 0)
    {
        $values = is_array($values) ? $values : [];

        return $this->finalizeInstance(
            $_class::fromArray($values, true)->setRequest($this->request), $multiple, 'body', $index
        );
    }

    public function finalizeInstance(Resource $resource, $multiple = false, string $group, int $index = 0)
    {
        $resource->prepare();
        $validation = $resource->validation($this->request);

        if ($multiple && 'body' === $group) {
            $this->request->_arrayBody = true;
            $group .= '.' . $index;
        }

        $validation = array_merge(
            $this->request->_validation[$group] ?? [],
            $validation
        );

        if (!$this->request->_validation) {
            $this->request->_validation = [];
        }

        $this->request->_validation[$group] = $validation;

        return $resource->flatten();
    }

    protected function validateJsonBody()
    {
        $body = $this->request->getContent();

        if (!$this->request->expectsJson() || empty($body)) {
            return true;
        }

        @json_decode($body);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidJsonException('Invalid json');
        }

        return true;
    }
}
