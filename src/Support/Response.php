<?php

namespace Seier\Resting\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Support\Responsable;
use Seier\Resting\Resource;

class Response implements Responsable
{
    private $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function fromResources(array $resources)
    {
        return new static(array_map(function (Resource $resource) {
            return $resource->toArray();
        }, $resources));
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'data' => $this->data,
        ];
    }

    public function toResponse($request)
    {
        return new JsonResponse([
            'data' => $this->data,
        ]);
    }
}
