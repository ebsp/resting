<?php


namespace Seier\Resting\Tests\Resources;


use Seier\Resting\UnionResource;

class ExtendsUnionResource extends UnionResource
{

    public function __construct()
    {
        parent::__construct('discriminator', [
            'a' => new UnionResourceA(),
            'b' => new UnionResourceB(),
        ]);
    }
}