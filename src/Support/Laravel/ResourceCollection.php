<?php

namespace Seier\Resting\Support\Laravel;


use Illuminate\Support\Collection;
use Seier\Resting\Support\Resourcable;
use Seier\Resting\Support\Transformer;
use Seier\Resting\Support\BaseTransformer;

class ResourceCollection extends Collection
{

    protected Transformer $transformer;

    public function __construct($items = [])
    {
        parent::__construct($items);

        $this->transformer = new BaseTransformer;
    }

    public function use(Transformer $transformer)
    {
        $this->transformer = $transformer;

        return $this;
    }

    public function from(Collection $collection): static
    {
        $transformer = $this->transformer;

        $this->items = $collection->map(function (Resourcable $factory) use ($transformer) {
            return $transformer($factory);
        })->all();

        return $this;
    }

    public static function fromCollection(Collection $collection, Transformer $transformer = null): ResourceCollection
    {
        return (new static)->use($transformer)->from(
            $collection
        );
    }
}
