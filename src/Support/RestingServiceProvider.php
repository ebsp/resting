<?php

namespace Seier\Resting\Support;

use Seier\Resting\Resource;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RestingServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \Illuminate\Routing\Route::macro('expects', function ($resourceClass) {
            $this->middleware(BuildResourceMiddleware::class . ':' . $resourceClass);

            return $this;
        });

        \Illuminate\Routing\Route::macro('query', function ($resourceClass) {
            $this->middleware(BuildQueryMiddleware::class . ':' . $resourceClass);

            return $this;
        });

        \Illuminate\Routing\Route::macro('returns', function ($resource) {
            $this->returnsSingleResource = $resource;

            return $this;
        });

        \Illuminate\Routing\Route::macro('lists', function ($resource) {
            $this->returnsListOfResources = $resource;

            return $this;
        });

        \Illuminate\Routing\Route::macro('docs', function ($text) {
            $this->_docs = $text;

            return $this;
        });

        $_self = $this;

        Collection::macro('toResources', function () use ($_self) {
            return $_self->collectionResponse(
                $_self->mapCollection($this)
            );
        });

        $paginatableMacro = function ($limit = 15) use ($_self) {
            $limit = function_exists('request') ? request()->query('limit', $limit) : $limit;

            return $_self->mapPagination(
                $this->paginate($limit)
            );
        };

        Relation::macro('asPaginatedResource', $paginatableMacro);

        Builder::macro('asPaginatedResource', $paginatableMacro);
    }

    public function mapPagination(LengthAwarePaginator $paginator)
    {
        $paginator->setCollection(
            $this->mapCollection(
                $paginator->getCollection()
            )
        );

        return $this->paginatedResponse($paginator);
    }

    public function mapCollection(Collection $collection)
    {
        return $collection->filter(function ($item) {
            return $item instanceof Resourcable || $item instanceof Resource;
        })->map(function ($item) {
            if ($item instanceof Resource) {
                return $item;
            }

            return $item->asResource()->toResponseArray();
        });
    }

    public function paginatedResponse(LengthAwarePaginator $paginator)
    {
        return new PaginatedResponse(
            $paginator->getCollection()->toArray(),
            $paginator->currentPage(),
            $paginator->perPage(),
            $paginator->total()
        );
    }

    public function collectionResponse(Collection $collection)
    {
        return new Response(
            $collection->toArray()
        );
    }
}
