<?php

/*
 * This file is part of the anik/laravel-query-logger.
 *
 * (c) ssi-anik <sirajul.islam.anik@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Anik\LaravelQueryLogger;

use Carbon\Carbon;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    private $file = '';
    private $logs = [];

    public function boot()
    {
    	// explicitly define that you need to log queries in production
		if ( false === env('LOG_DB_QUERIES', false)) {
			return;
		}

		$now = Carbon::now();

		$name = 'queries.log';
		if (env('LOG_DB_QUERIES') === 'daily') {
			$name = sprintf('queries-%s.log', $now->toDateString());
		}

		$this->file = $fileString = storage_path(sprintf('logs/%s', $name));

        $this->logs[] = sprintf('============ %s: %s ===============%s', app('request')->method(), app('request')->fullUrl(), PHP_EOL);
        app('db')->listen(function (QueryExecuted $query) use ($fileString, $now) {
            $sqlWithPlaceholders = str_replace(['%', '?'], ['%%', '%s'], $query->sql);

            $bindings = $query->connection->prepareBindings($query->bindings);
            $pdo = $query->connection->getPdo();
            $realSql = vsprintf($sqlWithPlaceholders, array_map([$pdo, 'quote'], $bindings));
            $duration = $this->formatDuration($query->time / 1000);

            $this->logs[] = sprintf("[%s] [%'.12s] %s%s", $now, $duration, $realSql, PHP_EOL);
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
            return round($seconds * 1000000).'μs';
        } elseif ($seconds < 1) {
            return round($seconds * 1000, 2).'ms';
        }

        return round($seconds, 2).'s';
    }

	public function __destruct () {
		// having log count 1 means it's the only url that's loaded at the beginning
		if (!empty($this->file) && !empty($this->logs) && count($this->logs) > 1){
			$this->logs[] = sprintf('%s%s%s%s', str_repeat('=', 50), str_repeat('=', 50),  PHP_EOL, PHP_EOL);
			app('files')->append($this->file, implode('', $this->logs));
		}
    }
}
