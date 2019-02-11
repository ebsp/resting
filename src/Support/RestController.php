<?php

namespace Seier\Resting\Support;

trait RestController
{
    public function callAction($method, $parameters)
    {
        app(ResourceRequest::class);

        return parent::callAction($method, $parameters);
    }
}
