<?php

/*
 * This file is part of the overtrue/laravel-query-logger.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\LaravelQueryLogger;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if (! $this->app['config']->get('logging.query.enabled', false)) {
            return;
        }

        $trigger = $this->app['config']->get('logging.query.trigger');

        if (! empty($trigger) && ! $this->requestHasTrigger($trigger)) {
            return;
        }

        $this->app['events']->listen(QueryExecuted::class, function (QueryExecuted $query) {
            if (
                $query->time < $this->app['config']->get('logging.query.slower_than', 0)
                || str($query->sql)->is($this->app['config']->get('logging.query.except', []))
            ) {
                return;
            }

            $realSql = $query->toRawSql();
            $duration = $this->formatDuration($query->time / 1000);

            Log::channel(config('logging.query.channel', config('logging.default')))
                ->debug(sprintf('[%s] [%s] %s | %s: %s', $query->connection->getDatabaseName(), $duration, $realSql,
                    request()->method(), request()->getRequestUri()));
        });
    }

    /**
     * Register the application services.
     */
    public function register() {}

    /**
     * @param  string  $trigger
     * @return bool
     */
    public function requestHasTrigger($trigger)
    {
        return getenv($trigger) !== false || \request()->hasHeader($trigger) || \request()->has($trigger) || \request()->hasCookie($trigger);
    }

    /**
     * Format duration.
     *
     * @param  float  $seconds
     * @return string
     */
    private function formatDuration($seconds)
    {
        if ($seconds < 0.001) {
            return round($seconds * 1000000).'μs';
        } elseif ($seconds < 1) {
            return round($seconds * 1000, 2).'ms';
        }

        return round($seconds, 2).'s';
    }
}
