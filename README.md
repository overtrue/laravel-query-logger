<h1 align="center"> Laravel Query Logger </h1>

<p align="center"> :pencil: A dev tool to log all queries for Laravel application.</p>

## Installing

```shell
$ composer require overtrue/laravel-query-logger --dev -vvv
```

Laravel Query Logger will be enabled when `APP_DEBUG` is `true`.

> Please keep the `--dev` option.

## Usage

```shell
$ tail -f ./storage/logs/laravel.log
```

    [2017-09-05 14:52:14] local.DEBUG: [800μs] select count(*) as aggregate from `discussions` where `discussions`.`deleted_at` is null | GET: http://laravel.app/discussions
    [2017-09-05 14:52:14] local.DEBUG: [1.07ms] select * from `discussions` where `discussions`.`deleted_at` is null order by `is_top` desc, `created_at` desc limit 15 offset 0 | GET: http://laravel.app/discussions
    [2017-09-05 14:52:14] local.DEBUG: [3.63s] select `tags`.*, `taggables`.`taggable_id` as `pivot_taggable_id`, `taggables`.`tag_id` as `pivot_tag_id` from `tags` inner join `taggables` on `tags`.`id` = `taggables`.`tag_id` where `taggables`.`taggable_id` in ('1', '2', '3', '4', '5', '6', '7', '8') and `taggables`.`taggable_type` = 'App\\Models\\Discussion' order by `order_column` asc | GET: http://laravel.app/discussions
    [2017-09-05 14:52:14] local.DEBUG: [670μs] select * from `users` where `users`.`id` in ('1', '2', '4') and `users`.`deleted_at` is null | GET: http://laravel.app/discussions
    ...
    
### Configuration

If you want to use it in a production environment, you can control whether or not to log a query via the configuration file：

*config/logging.php:*

```php
return [
    //...
    'query' => [
        'enabled' => env('LOG_QUERY', false),
         
        // Only record queries that are slower than the following time
        // Unit: milliseconds
        'slower_than' => 0, 
    ],
];
```


## PHP 扩展包开发

> 想知道如何从零开始构建 PHP 扩展包？
>
> 请关注我的实战课程，我会在此课程中分享一些扩展开发经验 —— [《PHP 扩展包实战教程 - 从入门到发布》](https://learnku.com/courses/creating-package)

## License

MIT
