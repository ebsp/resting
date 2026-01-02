<?php

namespace Seier\Resting\Support\Laravel;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Seier\Resting\Resource as RestingResource;
use Illuminate\Contracts\Support\Responsable;

class RestingResponse implements Responsable
{

    private mixed $data;
    private int $status;

    public function __construct(mixed $data, int $status = 200)
    {
        $this->data = $data;
        $this->status = $status;
    }

    public static function fromResources(array|Collection $resources, int $status = 200): static
    {
        $resources = $resources instanceof Collection ? $resources->toArray() : $resources;

        return new static(array_map(function ($resource) {
            return $resource instanceof RestingResource ? $resource->toResponseObject() : $resource;
        }, $resources), $status);
    }

    public static function fromResource(RestingResource $resource, int $status = 200): static
    {
        return new static(
            $resource->toResponseObject(),
            $status
        );
    }

    public function toArray(): array
    {
        return [
            'data' => $this->data,
        ];
    }

    public function toResponse($request): JsonResponse
    {
        return new JsonResponse([
            'data' => $this->data,
        ], $this->status);
    }
}
