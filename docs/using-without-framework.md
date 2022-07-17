# Framework-agnostic installation example

```php
use Telegram\Bot\Api;
use KootLabs\TelegramBotDialogs\DialogManager;
use KootLabs\TelegramBotDialogs\Dialogs\HelloExampleDialog;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

require __DIR__.'/vendor/autoload.php';

/** @todo replace by your token, {@see https://core.telegram.org/bots#6-botfather} */ 
$token = '110201543:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsaw';
$bot = new Api($token);
$cache = new FilesystemAdapter('', 0, __DIR__.'/../dialogs-cache');
$psr16Cache = new Psr16Cache($cache);
$dialogManager = new DialogManager($bot, $psr16Cache);

$dialog = new HelloExampleDialog($this->update->getChat()->id, $bot);
$dialogManager->activate($dialog);
$dialogManager->proceed($dialog);
```

a note for this example: The overhead of filesystem IO often makes this adapter one of the slower choices.
If throughput is paramount, the in-memory adapters (
[Apcu](https://symfony.com/doc/current/components/cache/adapters/apcu_adapter.html#apcu-adapter),
[Memcached](https://symfony.com/doc/current/components/cache/adapters/memcached_adapter.html#memcached-adapter), and
[Redis](https://symfony.com/doc/current/components/cache/adapters/redis_adapter.html#redis-adapter)) or the database adapters
([PDO](https://symfony.com/doc/current/components/cache/adapters/pdo_doctrine_dbal_adapter.html#pdo-doctrine-adapter)) are recommended.

Redis example:
```php
$redis = new \Redis();
$redis->connect('127.0.0.1', 6379);
$cache = new \Symfony\Component\Cache\Adapter\RedisAdapter($redis);
```
See [Redis Cache Adapter](https://symfony.com/doc/current/components/cache/adapters/redis_adapter.html#redis-adapter) for details.
