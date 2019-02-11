<?php

namespace Seier\Resting\Support;

use Illuminate\Routing\Route;
use Seier\Resting\Resource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\RouteCollection;
use Seier\Resting\Fields\FieldAbstract;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;

class OpenAPI implements Arrayable, Responsable
{
    public $document;

    protected $routes;
    protected $resources = [];
    protected $parameters = [];

    public function __construct(RouteCollection $collection)
    {
        $this->routes = $collection;

        $this->process();
    }

    protected function process()
    {
        $this->processInfo();
        $this->processPaths();
        $this->processResources();
        $this->processParameters();
    }

    protected function processInfo()
    {
        $this->document['openapi'] = '3.0.0';
        $this->document['info'] = [
            'version' => '1.0.0',
            'title' => 'Subit API',
        ];
    }

    protected function processParameters()
    {
        foreach ($this->parameters as $query => $_) {
            /** @var Resource $query */
            $query = new $query;

            $fields = $query->fields()->filter(function ($field) {
                return $field instanceof FieldAbstract;
            });

            $fields->each(function (FieldAbstract $abstract, $key) use ($query) {
                $this->document['components']['parameters'][
                    $this->parametersRefName(get_class($query), $key)
                ] = [
                    'in' => 'query',
                    'name' => $key,
                    'required' => $abstract->isRequired(),
                    'schema' => $abstract->type(),
                ];
            });
        }
    }

    protected function processResources()
    {
        foreach ($this->resources as $resource => $_) {
            $resource = new $resource;

            $fields = $resource->fields()->filter(function ($attr) {
                return $attr instanceof FieldAbstract;
            });

            $requiredFields = $fields->filter(function (FieldAbstract $field) {
                return $field->isRequired();
            });

            $this->document['components']['schemas'][
                $this->resourceRefName(get_class($resource))
            ] = [
                'type' => 'object',
                'required' => $requiredFields->map(function (FieldAbstract $field, $key) {
                    return $key;
                })->values()->toArray(),
                'properties' => $fields->map(function (FieldAbstract $field) {
                    $type = $field->type();

                    foreach ($field->nestedRefs() as $type => $ref) {
                        if ('schema' === $type) {
                            $this->addResource($ref);
                        } elseif ('parameters' === $type) {
                            $this->addParameter($ref);
                        }
                    }

                    return $type;
                })->toArray(),
            ];
        }
    }

    protected function processPaths()
    {
        $paths = [];

        foreach ($this->routes->getRoutes() as $route) {
            /** @var $route Route */
            $method = array_first(array_filter($route->methods(), function ($method) {
                return ! in_array($method, ['OPTIONS', 'HEAD']);
            }));

            $route->bind(new Request);

            $paths[
                '/' . $route->uri()
            ][strtolower($method)] = $this->describeEndpoint($route, $method);
        }

        $this->document['paths'] = $paths;
    }

    protected function describeEndpoint(Route $route, $method)
    {
        $endpoint = [
            'description' => $route->_docs ?? null,
            'responses' => [
                '200' => [
                    "content" => [
                        "application/json" => $this->describeResponse($route),
                    ]
                ]
            ],
        ];

        $expectsResource = array_first($route->gatherMiddleware(), function ($value) {
            return str_contains($value, BuildResourceMiddleware::class);
        }, false);

        if (in_array($method, ['POST', 'PATCH', 'PUT']) && $expectsResource) {
            $resource = array_last(explode(':', $expectsResource));
            $this->addResource($resource);

            $endpoint['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/' . $this->resourceRefName($resource),
                        ],
                    ],
                ],
            ];
        }

        $expectsQuery = array_first($route->gatherMiddleware(), function ($value) {
            return str_contains($value, BuildQueryMiddleware::class);
        }, false);

        if ($expectsQuery) {
            $query = array_last(explode(':', $expectsQuery));
            $this->addParameter($query);
            /** @var Resource $query */
            $query = new $query;

            $fields = $query->fields()->filter(function ($field) {
                return $field instanceof FieldAbstract;
            })->map(function (FieldAbstract $fieldAbstract, $key) use ($query) {
                return [
                    '$ref' => '#/components/parameters/' . $this->parametersRefName(get_class($query), $key)
                ];
            })->values();

            $endpoint['parameters'] = $fields->toArray();
        }

        return $endpoint;
    }

    protected function describeResponse(Route $route)
    {
        $response = [];

        if ($route->returnsSingleResource) {
            $this->addResource($route->returnsSingleResource);

            $response = [
                'schema' => [
                    '$ref' => '#/components/schemas/' . $this->resourceRefName($route->returnsSingleResource),
                ],
            ];
        } elseif ($route->returnsListOfResources) {
            $this->addResource($route->returnsListOfResources);

            $response = [
                'schema' => [
                    'type' => 'array',
                    'items' => [
                        '$ref' => '#/components/schemas/' . $this->resourceRefName($route->returnsListOfResources),
                    ],
                ],
            ];
        }

        return $response;
    }

    protected function addResource($resourceClass)
    {
        $this->resources[$resourceClass] = [];
    }

    protected function addParameter($queryClass)
    {
        $this->parameters[$queryClass] = [];
    }

    protected function resourceRefName($resourceClass)
    {
        return str_replace(['App\\Api\\Resources\\', '\\'], ['', '_'], $resourceClass);
    }

    protected function parametersRefName($queryClass, $propertyName)
    {
        return str_replace(['App\\Api\\Resources\\', '\\'], ['', '_'], $queryClass) . '_' . $propertyName;
    }

    public function toArray()
    {
        return $this->document;
    }

    public function toResponse($request)
    {
        return new JsonResponse(
            $this->toArray()
        );
    }
}
