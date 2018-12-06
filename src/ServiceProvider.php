<?php

/*
 * This file is part of the overtrue/package-builder.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\LaravelQueryLogger;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        // Config files
        $this->publishes([realpath(__DIR__) . '/config/laravel-query-logger.php' => config_path('laravel-query-logger.php')], 'config');
        $this->mergeConfigFrom(realpath(__DIR__) . '/config/laravel-query-logger.php', 'laravel-query-logger');

        // Disable if config is false
        if(config('laravel-query-logger.enabled', true)==false) {
            return;
        }

        // Start code
        DB::listen(function (QueryExecuted $query) {
            $sqlWithPlaceholders = str_replace(['%', '?'], ['%%', '%s'], $query->sql);
            $bindings = $query->connection->prepareBindings($query->bindings);
            $pdo = $query->connection->getPdo();
            $realSql = vsprintf($sqlWithPlaceholders, array_map([$pdo, 'quote'], $bindings));
            $seconds = $query->time / 1000;
            $duration = $this->formatDuration($seconds);
            if($seconds >= config('laravel-query-logger.min_size', 0)) {
                Log::debug(sprintf("[%s] %s \nMethod: %s\nUrl: %s", $duration, $realSql, request()->method(), request()->fullUrl()));    
            }
        });
    }

    /**
     * Register the application services.
     */
    public function register()
    {
    }

    /**
     * Format duration.
     *
     * @param float $seconds
     *
     * @return string
     */
    private function formatDuration($seconds)
    {
        if ($seconds < 0.001) {
            return round($seconds * 1000000) . 'μs';
        } elseif ($seconds < 1) {
            return round($seconds * 1000, 2) . 'ms';
        }

        return round($seconds, 2) . 's';
    }
}
