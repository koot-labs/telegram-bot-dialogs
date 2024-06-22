<?php

declare(strict_types=1);

return [
    /**
     * Cache store that should be used to store Dialog states between steps/requests.
     * This can be the name of any store that is configured in config/cache.php ("stores" key).
     */
    'cache_store' => env('TELEGRAM_DIALOGS_CACHE_DRIVER', 'database'),

    /** This prefix will be used in addition to Laravel’s prefix: "$laravelPrefix$telegramPrefix$key" */
    'cache_prefix' => env('TELEGRAM_DIALOGS_CACHE_PREFIX', 'tg_dialog_'),
];
