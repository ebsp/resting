<?php

namespace Seier\Resting\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait RestController
{
    /** @var Request */
    protected $request;

    public function callAction($method, $parameters)
    {
        $this->request = app(ResourceRequest::class);

        $result = parent::callAction($method, $parameters);

        if ($result instanceof Collection) {
            $result = $result->all();
        }

        if (is_array($result)) {
            $result = Response::fromResources($result);
        }

        return $result;
    }
}
