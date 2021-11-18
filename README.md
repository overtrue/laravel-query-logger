<h1 align="center"> Laravel Query Logger </h1>

<p align="center"> :pencil: A dev tool to log all queries for Laravel application.</p>

[![Sponsor me](https://raw.githubusercontent.com/overtrue/overtrue/master/sponsor-me-button-s.svg)](https://github.com/sponsors/overtrue)

## Installing

```shell
$ composer require overtrue/laravel-query-logger -vvv
```

Laravel Query Logger will be enabled when `LOG_QUERY` is `true`.

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

You can also control whether to log a query via the configuration file：

*config/logging.php:*

```php
return [
    //...
    'query' => [
        'enabled' => env('LOG_QUERY', env('APP_ENV') === 'local'),
         
        // Only record queries that are slower than the following time
        // Unit: milliseconds
        'slower_than' => 0, 
        
        // Only record queries when the QUERY_LOG_TRIGGER is set in the environment, 
        // or when the trigger HEADER, GET, POST, or COOKIE variable is set.
        'trigger' => env('QUERY_LOG_TRIGGER'), 
        
        // Log Channel
        'channel' => 'stack',
    ],
];
```

## :heart: Sponsor me 

[![Sponsor me](https://raw.githubusercontent.com/overtrue/overtrue/master/sponsor-me.svg)](https://github.com/sponsors/overtrue)

如果你喜欢我的项目并想支持它，[点击这里 :heart:](https://github.com/sponsors/overtrue)


## PHP 扩展包开发

> 想知道如何从零开始构建 PHP 扩展包？
>
> 请关注我的实战课程，我会在此课程中分享一些扩展开发经验 —— [《PHP 扩展包实战教程 - 从入门到发布》](https://learnku.com/courses/creating-package)

## License

MIT
