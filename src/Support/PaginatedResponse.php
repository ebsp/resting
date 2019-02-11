<?php

namespace Seier\Resting\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Support\Responsable;

class PaginatedResponse implements Responsable
{
    private $data = [];
    private $page;
    private $limit;
    private $total;

    public function __construct(array $data, int $page, int $limit, int $total)
    {
        $this->data = $data;
        $this->page = $page;
        $this->limit = $limit;
        $this->total = $total;
    }

    public function toResponse($request)
    {
        return new JsonResponse([
            'data' => $this->data,
            'page' => $this->page,
            'limit' => $this->limit,
            'total' => $this->total,
        ]);
    }
}