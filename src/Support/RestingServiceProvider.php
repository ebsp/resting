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
        $this->mergeConfigFrom(
            $configPath = __DIR__.'/../config/resting.php', 'resting'
        );

        $this->publishes([
            $configPath => config_path('resting.php')
        ], 'config');

        $this->app->bind('restingValidator', function () {
            return $this->app->get('validator');
        });

        \Illuminate\Support\Facades\Validator::resolver(function($translator, $data, $rules, $messages) {
            return new RestValidator($translator, $data, $rules, $messages);
        });

        \Illuminate\Routing\Route::macro('rest', function () {
            $this->middleware(RestMiddleware::class);

            return $this;
        });

        \Illuminate\Routing\Route::macro('lists', function ($resource = null) {
            $this->_lists = $resource;

            return $this;
        });

        \Illuminate\Routing\Route::macro('docs', function ($text) {
            $this->_docs = $text;

            return $this;
        });

        \Illuminate\Routing\Route::macro('pushParameter', function ($value) {
            $this->parameters[] = $value;

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
