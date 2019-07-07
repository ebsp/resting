<?php

namespace Seier\Resting\Tests\Support;


use Illuminate\Support\ServiceProvider;

class RestingTestServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->bind('restingValidator', function () {
            return new TestValidator;
        });
    }
}
