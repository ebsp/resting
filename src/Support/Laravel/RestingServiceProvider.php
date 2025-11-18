<?php

namespace Seier\Resting\Support\Laravel;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Seier\Resting\Resource;
use PHPUnit\Framework\Assert;
use Illuminate\Support\Collection;
use Seier\Resting\Support\Resourcable;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RestingServiceProvider extends ServiceProvider
{

    public function boot()
    {

        $this->mergeConfigFrom(
            $configPath = __DIR__ . '/../../config/resting.php', 'resting'
        );

        $this->publishes([
            $configPath => config_path('resting.php')
        ], 'config');

        $searchForError = function (array $errors, string $key): ?array {
            foreach ($errors as $error) {
                if (array_key_exists('path', $error) && $error['path'] === $key) {
                    return $error;
                }
            }

            return null;
        };

        $macroName = 'assertNestedJsonValidationErrors';
        $macroAction = function ($errors, $group = 'body') use ($searchForError) {
            $errors = Arr::wrap($errors);

            Assert::assertNotEmpty($errors, 'No validation errors were provided.');

            $jsonErrors = $this->json()['errors'][$group] ?? [];

            $errorMessage = $jsonErrors
                ? 'RestingResponse has the following JSON validation errors:' .
                PHP_EOL . PHP_EOL . json_encode($jsonErrors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL
                : 'RestingResponse does not have JSON validation errors.';

            foreach ($errors as $key => $value) {

                $error = $searchForError($jsonErrors, is_int($key) ? $value : $key);
                if ($error === null) {
                    Assert::fail(
                        "Failed to find a validation error in the response for key: '{$value}'" . PHP_EOL . PHP_EOL . $errorMessage
                    );
                }

                if (!is_int($key)) {

                    if (array_key_exists('message', $error) && Str::contains($error['message'], $value)) {
                        return $this;
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
            $this->middleware(RestingMiddleware::class);

            return $this;
        });

        \Illuminate\Routing\Route::macro('lists', function (...$resources) {
            $this->defaults['_lists'] = $resources;

            return $this;
        });

        \Illuminate\Routing\Route::macro('docs', function ($text) {
            $this->defaults['_docs'] = $text;

            return $this;
        });

        $_self = $this;

        $paginatableMacro = function ($limit = 15, ?callable $resourceMapper = null) use ($_self) {
            $request = app()->get('request');
            $limit = optional($request)->query('limit', $limit) ?? $limit;

            return $_self->mapPagination(
                $this->paginate($limit), $resourceMapper
            );
        };

        Relation::macro('asPaginatedResource', $paginatableMacro);
        Builder::macro('asPaginatedResource', $paginatableMacro);
    }

    public function mapPagination(LengthAwarePaginator $paginator, ?callable $resourceMapper = null): PaginatedResponse
    {
        $paginator->setCollection(
            $this->mapCollection(
                $paginator->getCollection(), $resourceMapper
            )
        );

        return $this->paginatedResponse($paginator);
    }

    public function mapCollection(Collection $collection, ?callable $resourceMapper = null): Collection
    {
        return $collection->filter(function ($item) {
            return $item instanceof Resourcable || $item instanceof Resource;
        })->map(function ($item) use ($resourceMapper) {
            if ($item instanceof Resource) {
                return $item;
            }

            $item = $resourceMapper ? $resourceMapper($item) : $item->asResource();
            return $item->toResponseArray();
        });
    }

    public function paginatedResponse(LengthAwarePaginator $paginator): PaginatedResponse
    {
        return new PaginatedResponse(
            $paginator->getCollection()->toArray(),
            $paginator->currentPage(),
            $paginator->perPage(),
            $paginator->total()
        );
    }
}
