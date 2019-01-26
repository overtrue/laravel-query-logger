<h1 align="center"> Laravel/Lumen Query Logger </h1>

<p align="center"> :pencil: A dev tool to log all queries for Laravel/Lumen application.</p>

## Installing

```shell
$ composer require anik/laravel-query-logger --dev
```

> Please keep the `--dev` option.

## Usage

1. You need to EXPLICITLY define `LOG_DB_QUERIES` to some values you wish. `(bool) false` will not log any query.
2. If `LOG_DB_QUERIES` is set to `daily`, it'll log on daily basis.
3. For `Lumen` you must have to register the `ServiceProvider` in `bootstrap/app.php`.

```php
$app->register(Anik\LaravelQueryLogger\ServiceProvider::class);
```
```shell
$ tail -f storage/logs/queries.log
```
Or
```shell
$ tail -f storage/logs/queries-2019-01-27.log
```

    ============ URL: http://laravel.app/discussions ===============
    [2019-01-27 18:52:14] [.....800μs] select count(*) as aggregate from `discussions` where `discussions`.`deleted_at` is null
    [2019-01-27 18:52:14] [....1.07ms] select * from `discussions` where `discussions`.`deleted_at` is null order by `is_top` desc, `created_at` desc limit 15 offset 0
    [2019-01-27 18:52:14] [.....3.63s] select `tags`.*, `taggables`.`taggable_id` as `pivot_taggable_id`, `taggables`.`tag_id` as `pivot_tag_id` from `tags` inner join `taggables` on `tags`.`id` = `taggables`.`tag_id` where `taggables`.`taggable_id` in ('1', '2', '3', '4', '5', '6', '7', '8') and `taggables`.`taggable_type` = 'App\\Models\\Discussion' order by `order_column` asc
    [2019-01-27 18:52:14] [.....670μs] select * from `users` where `users`.`id` in ('1', '2', '4') and `users`.`deleted_at` is null
    ================================================================================
    
## License

MIT
