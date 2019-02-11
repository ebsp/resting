<?php

namespace Seier\Resting\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Support\Responsable;

class Response implements Responsable
{
    private $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
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