<?php

namespace Seier\Resting\Support;

trait RestingTest
{
    protected function createTestResponse($response)
    {
        return TestResponse::fromBaseResponse($response);
    }
}
