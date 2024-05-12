# Framework-agnostic installation example

1. Install the package via composer
2. Install `symfony/cache` via composer
3. Install a PSR-16 storage (to store dialog states between requests), for example `composer require symfony/cache`: 
   - a. If you have Redis installed, you can use File driver (see example below)
   - b. You have Redis installed: See `RedisAdapter` example below

File storage example.
```php
use KootLabs\TelegramBotDialogs\DialogManager;
use KootLabs\TelegramBotDialogs\Dialogs\HelloExampleDialog;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

require __DIR__.'/vendor/autoload.php';

/** @todo replace by your token, {@see https://core.telegram.org/bots#6-botfather} */ 
$token = '110201543:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsaw';
$bot = new \Telegram\Bot\Api($token);

$store = new Psr16Cache(new FilesystemAdapter('', 0, __DIR__.'/store/'));
$dialogManager = new DialogManager($bot, $store);

// A simplified example for updates polling. We recommend you using webhooks instead.
$updates = $bot->getUpdates(['offset' => $store->get('latest_update_id') + 1]);

foreach ($updates as $update) {
    echo 'Received update: '.$update->update_id.PHP_EOL;
    if (! $dialogManager->exists($update)) {
        $dialog = new HelloExampleDialog($update->getChat()->id, $bot);
        $dialogManager->activate($dialog);
    }
    $dialogManager->proceed($update);
}

if (isset($update)) {
    $store->set('latest_update_id', $update->update_id);
} else {
    echo 'No updates received'.PHP_EOL;
}
```

> [!IMPORTANT]  
> a note for this example: The overhead of filesystem IO (FilesystemAdapter) often makes this adapter one of the slower choices.
> If throughput is paramount, the in-memory adapters (
> [Apcu](https://symfony.com/doc/current/components/cache/adapters/apcu_adapter.html#apcu-adapter),
> [Memcached](https://symfony.com/doc/current/components/cache/adapters/memcached_adapter.html#memcached-adapter), and
> [Redis](https://symfony.com/doc/current/components/cache/adapters/redis_adapter.html#redis-adapter)) or the database adapters
> ([PDO](https://symfony.com/doc/current/components/cache/adapters/pdo_doctrine_dbal_adapter.html#pdo-doctrine-adapter)) are recommended.

Init redis store example (if you have Redis installed):
```diff
-$store = new Psr16Cache(new FilesystemAdapter('', 0, __DIR__.'/store/'));
+$redis = new \Redis();
+$redis->connect('127.0.0.1', 6379);
+$store = new Psr16Cache(new \Symfony\Component\Cache\Adapter\RedisAdapter($redis));
```
See [Redis Cache Adapter](https://symfony.com/doc/current/components/cache/adapters/redis_adapter.html#redis-adapter) for details.
