<?php

namespace Seier\Resting\Tests;

use Seier\Resting\Support\RestingTest;
use Orchestra\Testbench\TestCase as Orchestra;
use Seier\Resting\Support\RestingServiceProvider;
use Seier\Resting\Tests\Support\RestingTestServiceProvider;

abstract class TestCase extends Orchestra
{
    use RestingTest;

    public function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            RestingServiceProvider::class,
            RestingTestServiceProvider::class,
        ];
    }
}
