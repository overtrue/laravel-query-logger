<?php

namespace Overtrue\LaravelQueryLogger\Tests;

use DateTimeImmutable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use Overtrue\LaravelQueryLogger\ServiceProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;

class ServiceProviderTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
    }

    protected function enableQueryLogging($app): void
    {
        $app['config']->set('logging.query.enabled', true);
        $app['config']->set('logging.query.channel', 'queries');
    }

    #[DefineEnvironment('enableQueryLogging')]
    public function test_it_logs_a_real_database_query_after_the_provider_boots(): void
    {
        $this->assertInstanceOf(ServiceProvider::class, $this->app->getProvider(ServiceProvider::class));
        $this->app->instance('request', Request::create('/reports?active=1', 'POST'));

        $logger = Mockery::mock(LoggerInterface::class);
        Log::shouldReceive('channel')->once()->with('queries')->andReturn($logger);
        $logger->shouldReceive('debug')->once()->withArgs(function (string $message): bool {
            $this->assertMatchesRegularExpression(
                '/^\[:memory:\] \[\d+(?:\.\d+)?(?:μs|ms|s)\] select \'hello\' as greeting \| POST: \/reports\?active=1$/u',
                $message
            );

            return true;
        });

        $rows = DB::select('select ? as greeting', ['hello']);

        $this->assertSame('hello', $rows[0]->greeting);
    }

    #[DataProvider('durations')]
    #[DefineEnvironment('enableQueryLogging')]
    public function test_it_formats_query_duration(float $milliseconds, string $formatted): void
    {
        $this->expectLog("[:memory:] [{$formatted}] select 1 | GET: /");

        $this->dispatchQuery('select 1', [], $milliseconds);
    }

    public static function durations(): array
    {
        return [
            'zero' => [0, '0μs'],
            'microseconds' => [0.8, '800μs'],
            'millisecond boundary' => [1, '1ms'],
            'rounded milliseconds' => [15.257, '15.26ms'],
            'below one second' => [999.99, '999.99ms'],
            'second boundary' => [1000, '1s'],
            'rounded seconds' => [1234.567, '1.23s'],
        ];
    }

    #[DefineEnvironment('enableQueryLogging')]
    public function test_it_prepares_and_quotes_bindings(): void
    {
        $this->expectLog("[:memory:] [10ms] select 'O''Reilly', '42', '1.5', '1', '0', NULL, '2026-10-07 12:34:56' | GET: /");

        $this->dispatchQuery('select ?, ?, ?, ?, ?, ?, ?', [
            "O'Reilly", 42, 1.5, true, false, null, new DateTimeImmutable('2026-10-07 12:34:56'),
        ]);
    }

    #[DefineEnvironment('enableQueryLogging')]
    public function test_it_preserves_percent_signs_when_interpolating_bindings(): void
    {
        $this->expectLog("[:memory:] [10ms] select '50%' as amount, '100%' as total | GET: /");

        $this->dispatchQuery("select ? as amount, '100%' as total", ['50%']);
    }

    #[DefineEnvironment('enableQueryLogging')]
    public function test_it_uses_the_default_log_channel_when_no_query_channel_is_configured(): void
    {
        $queryConfig = $this->app['config']->get('logging.query');
        unset($queryConfig['channel']);
        $this->app['config']->set('logging.query', $queryConfig);
        $this->app['config']->set('logging.default', 'daily');
        $this->expectLog('[:memory:] [10ms] select 1 | GET: /', 'daily');

        $this->dispatchQuery();
    }

    #[DefineEnvironment('enableQueryLogging')]
    public function test_it_skips_queries_below_the_threshold_but_logs_at_the_boundary(): void
    {
        $this->app['config']->set('logging.query.slower_than', 10);
        $this->expectLog('[:memory:] [10ms] select 1 | GET: /');

        $this->dispatchQuery('select 1', [], 9.99);
        $this->dispatchQuery('select 1', [], 10);
    }

    #[DefineEnvironment('enableQueryLogging')]
    public function test_it_logs_queries_above_the_threshold(): void
    {
        $this->app['config']->set('logging.query.slower_than', 10);
        $this->expectLog('[:memory:] [20ms] select 1 | GET: /');

        $this->dispatchQuery('select 1', [], 20);
    }

    #[DefineEnvironment('enableQueryLogging')]
    public function test_it_excludes_matching_sql_and_logs_other_queries(): void
    {
        $this->app['config']->set('logging.query.except', ['*_telescope_*', 'select 2', 'select ? as name']);
        $this->expectLog('[:memory:] [10ms] select 1 | GET: /');

        $this->dispatchQuery('select * from app_telescope_entries');
        $this->dispatchQuery('select 2');
        $this->dispatchQuery('select ? as name', ['Alice']);
        $this->dispatchQuery('select 1');
    }

    #[DefineEnvironment('disableQueryLogging')]
    public function test_it_does_not_register_a_listener_when_disabled(): void
    {
        Log::shouldReceive('channel')->never();
        $this->assertFalse($this->app['events']->hasListeners(QueryExecuted::class));

        $this->dispatchQuery();
    }

    protected function disableQueryLogging($app): void
    {
        $app['config']->set('logging.query.enabled', false);
    }

    public function test_it_is_disabled_when_configuration_is_missing(): void
    {
        Log::shouldReceive('channel')->never();
        $this->assertFalse($this->app['events']->hasListeners(QueryExecuted::class));

        $this->dispatchQuery();
    }

    #[DefineEnvironment('requireMissingTrigger')]
    public function test_it_does_not_log_without_the_required_trigger(): void
    {
        Log::shouldReceive('channel')->never();
        $this->assertFalse($this->app['events']->hasListeners(QueryExecuted::class));

        $this->dispatchQuery();
    }

    protected function requireMissingTrigger($app): void
    {
        $this->enableQueryLogging($app);
        $app['config']->set('logging.query.trigger', 'QUERY_LOGGER_TEST_TRIGGER');
    }

    #[DefineEnvironment('provideHeaderTrigger')]
    public function test_it_registers_the_listener_when_the_trigger_is_present_at_boot(): void
    {
        $this->expectLog('[:memory:] [10ms] select 1 | GET: /');

        $this->dispatchQuery();
    }

    protected function provideHeaderTrigger($app): void
    {
        $this->enableQueryLogging($app);
        $app['config']->set('logging.query.trigger', 'X-Query-Log');
        $app['request']->headers->set('X-Query-Log', '');
    }

    #[DataProvider('triggerSources')]
    public function test_it_recognizes_request_trigger_sources(string $source): void
    {
        $request = Request::create('/', $source === 'post' ? 'POST' : 'GET');

        match ($source) {
            'header' => $request->headers->set('QUERY_LOGGER_TEST_TRIGGER', ''),
            'get' => $request->query->set('QUERY_LOGGER_TEST_TRIGGER', ''),
            'post' => $request->request->set('QUERY_LOGGER_TEST_TRIGGER', ''),
            'cookie' => $request->cookies->set('QUERY_LOGGER_TEST_TRIGGER', ''),
        };
        $this->app->instance('request', $request);

        $provider = $this->app->getProvider(ServiceProvider::class);
        $this->assertTrue($provider->requestHasTrigger('QUERY_LOGGER_TEST_TRIGGER'));
        $this->assertFalse($provider->requestHasTrigger('QUERY_LOGGER_MISSING_TRIGGER'));
    }

    public static function triggerSources(): array
    {
        return [
            'header' => ['header'],
            'query string' => ['get'],
            'form body' => ['post'],
            'cookie' => ['cookie'],
        ];
    }

    public function test_it_recognizes_an_environment_trigger_even_when_empty(): void
    {
        $name = 'QUERY_LOGGER_TEST_ENV_TRIGGER';
        $previous = getenv($name);

        try {
            putenv($name.'=');
            $this->assertTrue($this->app->getProvider(ServiceProvider::class)->requestHasTrigger($name));
        } finally {
            putenv($previous === false ? $name : $name.'='.$previous);
        }
    }

    private function expectLog(string $message, string $channel = 'queries'): void
    {
        $logger = Mockery::mock(LoggerInterface::class);
        Log::shouldReceive('channel')->once()->with($channel)->andReturn($logger);
        $logger->shouldReceive('debug')->once()->with($message);
    }

    private function dispatchQuery(string $sql = 'select 1', array $bindings = [], float $time = 10): void
    {
        $this->app['events']->dispatch(new QueryExecuted($sql, $bindings, $time, DB::connection()));
    }
}
