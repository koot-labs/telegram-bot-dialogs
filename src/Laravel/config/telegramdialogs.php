<?php

declare(strict_types=1);

return [
    /**
     * Stores to store Dialog states.
     */
    'stores' => [
        'redis' => [
            'connection' => env('TELEGRAM_DIALOGS_REDIS_CONNECTION', 'default'),
        ],
    ],

    /*
     * If the cache driver you configured supports tags, you may specify a tag name here.
     * All stored dialogs will be tagged. When clearing, the dialogs only items with that tag will be flushed.
     * @see https://laravel.com/docs/9.x/cache#cache-tags
     * You may use a string or an array here.
     */
    'cache_tag' => '',
];
