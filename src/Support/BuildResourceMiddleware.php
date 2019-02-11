<?php

namespace Seier\Resting\Support;

use Closure;
use Seier\Resting\Resource;
use Illuminate\Http\Request;

class BuildResourceMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $resourceClass)
    {
        /** @var Resource $resource */
        $resource = $resourceClass::fromArray(
            $request->all(), false
        );

        $resource->prepare();

        $request->route()->_resource = $resource;
        $request->route()->setParameter(
            $resourceClass,
            $resource->flatten()
        );

        return $next($request);
    }
}