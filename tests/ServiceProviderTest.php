<?php

namespace Overtrue\LaravelQueryLogger\Tests;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Overtrue\LaravelQueryLogger\ServiceProvider;
use Psr\Log\LoggerInterface;

class ServiceProviderTest extends TestCase
{
    public function test_it_can_boot_the_package(): void
    {
        $this->assertTrue(true);
    }

    /**
     * 验证 JSONPath 字面量问号不会被误认为 SQL 绑定占位符
     *
     * @return void
     */
    public function test_it_logs_jsonpath_queries_with_literal_question_marks(): void
    {
        // 配置查询日志并重新注册服务提供者，使监听器在测试中启用
        config([
            'logging.query.enabled' => true,
            'logging.query.channel' => 'testing',
        ]);
        $this->app->register(ServiceProvider::class, true);

        // 断言日志通道收到同时保留 JSONPath 问号和插值后绑定值的 SQL
        $logger = Mockery::mock(LoggerInterface::class);
        Log::shouldReceive('channel')->once()->with('testing')->andReturn($logger);
        $logger->shouldReceive('debug')->once()->with(Mockery::on(function (string $message): bool {
            $this->assertStringContainsString("'$.*[*] ? (@ == \$username)'", $message);
            $this->assertStringContainsString("to_jsonb(CAST('C0054917' AS text))", $message);

            return true;
        }));

        // 派发含一个真实占位符的查询事件，避免依赖 PostgreSQL 服务
        $query = new QueryExecuted(
            <<<'SQL'
select jsonb_path_exists(stage_dris, '$.*[*] ? (@ == $username)', jsonb_build_object('username', to_jsonb(CAST(? AS text))))
SQL,
            ['A123456'],
            1,
            DB::connection(),
        );

        $this->app['events']->dispatch($query);
    }
}
