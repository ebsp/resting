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

        return parent::callAction($method, $parameters);
    }
}
