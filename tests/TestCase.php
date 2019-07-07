<?php

namespace Seier\Resting\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Seier\Resting\Support\RestingServiceProvider;
use Seier\Resting\Tests\Support\RestingTestServiceProvider;
use Spatie\ValidationRules\ValidationRulesServiceProvider;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;

abstract class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();

        $this->app->make(EloquentFactory::class)->load(__DIR__.'/factories');
    }

    protected function getPackageProviders($app)
    {
        return [
            RestingServiceProvider::class,
            RestingTestServiceProvider::class,
        ];
    }
}
