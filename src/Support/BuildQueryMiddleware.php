<?php

namespace Seier\Resting\Support;

use Closure;
use Seier\Resting\Resource;
use Illuminate\Http\Request;

class BuildQueryMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $queryClass)
    {
        /** @var Resource $query */
        $query = $queryClass::fromArray(
            $request->query(), false
        );

        $query->prepare();

        $request->route()->_query = $query;
        $request->route()->setParameter(
            $queryClass,
            $query->flatten()
        );

        return $next($request);
    }
}