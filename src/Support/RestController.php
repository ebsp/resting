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
        $this->request = app($this->formRequestClass());

        $result = parent::callAction($method, $parameters);

        if ($result instanceof Collection) {
            $result = $result->all();
        } elseif ($result instanceof Resourcable) {
            $result = $result->asResource();
        }

        if (is_array($result)) {
            $result = Response::fromResources($result);
        }

        return $result;
    }

    private function formRequestClass()
    {
        return config('resting.form_request');
    }
}
