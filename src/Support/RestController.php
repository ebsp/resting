<?php

namespace Seier\Resting\Support;

use Exception;
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
            $result = $this->transform($result);
        }

        if (is_array($result)) {
            $result = Response::fromResources($result);
        }

        return $result;
    }

    public function transform($data, Transformer $transformer = null)
    {
        if (! $transformer) {
            $transformer = new BaseTransformer;
        }

        if ($data instanceof Collection) {
            return ResourceCollection::fromCollection($data, $transformer);
        }

        if ($data instanceof Resourcable) {
            return $transformer($data);
        }

        throw new Exception;
    }

    private function formRequestClass()
    {
        return config('resting.form_request');
    }
}
