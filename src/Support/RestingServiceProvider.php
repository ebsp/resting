<?php

namespace Seier\Resting\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Seier\Resting\Resource;
use PHPUnit\Framework\Assert;
use Illuminate\Support\Collection;
use Illuminate\Routing\Redirector;
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
            $configPath = __DIR__ . '/../config/resting.php', 'resting'
        );

        $this->publishes([
            $configPath => config_path('resting.php')
        ], 'config');

        $this->app->bind('restingValidator', function () {
            return $this->app->get('validator');
        });

        \Illuminate\Support\Facades\Validator::resolver(function ($translator, $data, $rules, $messages) {
            return new RestValidator($translator, $data, $rules, $messages);
        });

        \Illuminate\Support\Facades\Validator::extendImplicit('valid_timestamp', function ($attribute, $value, $parameters, $validator) {

            [$required] = $parameters;

            if ($required === 'required') {
                return !!$value;
            }

            if ($required === 'nullable') {
                return true;
            }

            return false;
        });

        $this->app->resolving(FormRequest::class, function ($request, $app) {
            $request = FormRequest::createFrom($app['request'], $request);
            $request->setContainer($app)->setRedirector($app->make(Redirector::class));
        });

        $macroName = 'assertNestedJsonValidationErrors';
        $macroAction = function ($errors, $group = 'body') {
            $errors = Arr::wrap($errors);

            Assert::assertNotEmpty($errors, 'No validation errors were provided.');

            $jsonErrors = $this->json()['errors'][$group] ?? [];

            $errorMessage = $jsonErrors
                ? 'Response has the following JSON validation errors:' .
                PHP_EOL . PHP_EOL . json_encode($jsonErrors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL
                : 'Response does not have JSON validation errors.';

            foreach ($errors as $key => $value) {
                Assert::assertArrayHasKey(
                    (is_int($key)) ? $value : $key,
                    $jsonErrors,
                    "Failed to find a validation error in the response for key: '{$value}'" . PHP_EOL . PHP_EOL . $errorMessage
                );

                if (!is_int($key)) {
                    foreach (Arr::wrap($jsonErrors[$key]) as $jsonErrorMessage) {
                        if (Str::contains($jsonErrorMessage, $value)) {
                            return $this;
                        }
                    }

                    Assert::fail(
                        "Failed to find a validation error in the response for key and message: '$key' => '$value'" . PHP_EOL . PHP_EOL . $errorMessage
                    );
                }
            }

            return $this;
        };

        if (class_exists('\Illuminate\Foundation\Testing\TestResponse')) {
            \Illuminate\Foundation\Testing\TestResponse::macro($macroName, $macroAction);
        }

        if (class_exists('\Illuminate\Testing\TestResponse')) {
            \Illuminate\Testing\TestResponse::macro($macroName, $macroAction);
        }

        \Illuminate\Routing\Route::macro('rest', function () {
            $this->middleware(RestMiddleware::class);

            return $this;
        });

        \Illuminate\Routing\Route::macro('lists', function (...$resources) {
            $this->_lists = $resources;

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

        $request = $this->app->get('request');

        $paginatableMacro = function ($limit = 15) use ($request, $_self) {
            $limit = optional($request)->query('limit', $limit) ?? $limit;

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
