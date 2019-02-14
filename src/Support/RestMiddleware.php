<?php

namespace Seier\Resting\Support;

use Closure;
use ReflectionClass;
use Seier\Resting\Resource;
use Illuminate\Http\Request;

class RestMiddleware
{
    protected $request;

    public function handle(Request $request, Closure $next)
    {
        $this->request = $request;

        foreach ($request->route()->signatureParameters() as $parameter) {
            /** @var \ReflectionParameter $parameter */
            $type = $parameter->getType();

            if (! $type) {
                continue;
            }

            $reflectionClass = new ReflectionClass($type->getName());

            if ($reflectionClass->isInstantiable()) {
                $value = $this->resolveParameter($reflectionClass);

                if ($value) {
                    $request->route()->setParameter($parameter->getName(), $value);
                }
            }
        }

        return $next($request);
    }

    protected function resolveParameter(ReflectionClass $_class)
    {
        if ($_class->isSubclassOf(Resource::class)) {
            $_className = $_class->getName();
            $resource = $_className::fromRequest($this->request);
            $resource->prepare();


            $this->request->_validation = array_merge(
                $this->request->_validation ?? [],
                $resource->validation($this->request)
            );

            return $resource->flatten();
        }

        return null;
    }
}
