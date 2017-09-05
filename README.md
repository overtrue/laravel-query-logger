<h1 align="center"> Laravel Query Logger </h1>

<p align="center"> :pencil: A dev tool to log all queries for laravel application.</p> 

## Installing

```shell
$ composer require overtrue/laravel-query-logger --dev -vvv
```

> Plz keep the `--dev` option.

## Usage

```php
$ tail -f ./storage/logs/laravel.log 
```

    [2017-09-05 14:52:13] local.INFO: ============ URL: http://laravel.app/discussions ===============
    [2017-09-05 14:52:14] local.INFO: select count(*) as aggregate from `discussions` where `discussions`.`deleted_at` is null
    [2017-09-05 14:52:14] local.INFO: select * from `discussions` where `discussions`.`deleted_at` is null order by `is_top` desc, `created_at` desc limit 15 offset 0
    [2017-09-05 14:52:14] local.INFO: select `tags`.*, `taggables`.`taggable_id` as `pivot_taggable_id`, `taggables`.`tag_id` as `pivot_tag_id` from `tags` inner join `taggables` on `tags`.`id` = `taggables`.`tag_id` where `taggables`.`taggable_id` in ('1', '2', '3', '4', '5', '6', '7', '8') and `taggables`.`taggable_type` = 'App\\Models\\Discussion' order by `order_column` asc
    [2017-09-05 14:52:14] local.INFO: select * from `users` where `users`.`id` in ('1', '2', '4') and `users`.`deleted_at` is null
    ...
## License

MIT
