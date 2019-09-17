<?php

namespace Seier\Resting\Support;


use Illuminate\Support\Collection;

class ResourceCollection extends Collection
{
    protected $transformer;

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

    public function from(Collection $collection)
    {
        $transformer = $this->transformer;

        $this->items = $collection->map(function (Resourcable $resourcable) use ($transformer) {
            return $transformer($resourcable);
        })->all();

        return $this;
    }

    public static function fromCollection(Collection $collection, Transformer $transformer = null)
    {
        return (new static)->use($transformer)->from(
            $collection
        );
    }
}
