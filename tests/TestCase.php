<?php

namespace Overtrue\LaravelQueryLogger\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Overtrue\LaravelQueryLogger\ServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }
}
