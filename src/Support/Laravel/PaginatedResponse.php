<?php

namespace Seier\Resting\Support\Laravel;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Support\Responsable;

class PaginatedResponse implements Responsable
{

    private array $data;
    private int $page;
    private int $limit;
    private int $total;

    public function __construct(array $data, int $page, int $limit, int $total)
    {
        $this->data = $data;
        $this->page = $page;
        $this->limit = $limit;
        $this->total = $total;
    }

    public function toResponse($request): JsonResponse
    {
        return new JsonResponse([
            'data' => $this->data,
            'page' => $this->page,
            'limit' => $this->limit,
            'total' => $this->total,
        ]);
    }
}