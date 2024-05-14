<?php

declare(strict_types=1);

return [
    /*
     * Cache store that should be used to store Dialog states between steps/requests.
     * This can be the name of any store that is configured in config/cache.php ("stores" key).
     */
    'cache_store' => env('TELEGRAM_DIALOGS_CACHE_DRIVER', 'database'),

    /*
     * If the cache driver you configured supports tags, you may specify a tag name here.
     * All stored dialogs will be tagged. When clearing, the dialogs only items with that tag will be flushed.
     * @see https://laravel.com/docs/9.x/cache#cache-tags
     * You may use a string or an array here.
     * This prefix will be used in addition to Laravel’s prefix: "$laravelPrefix$telegramPrefix$key"
     */
    'cache_tag' => env('TELEGRAM_DIALOGS_CACHE_TAG', 'tg_dialog_'),
];
