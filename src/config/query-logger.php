<?php

/*
 * This file is part of the overtrue/laravel-query-logger.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

return [

	/*
     * Enable or disable
     */

    'enabled' => env('QUERY_LOGGER_ENABLED', true),

    /*
     * Min size of query to log in miliseconds
     */

    'miliseconds' => 0,

];
