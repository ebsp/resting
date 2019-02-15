<?php

namespace Seier\Resting\Support;

use Illuminate\Http\Request;

trait RestController
{
    /** @var Request */
    protected $request;

    public function callAction($method, $parameters)
    {
        $this->request = app(ResourceRequest::class);

        $result = parent::callAction($method, $parameters);

        if (is_array($result)) {
            $result = Response::fromResources($result);
        }

        return $result;
    }
}
