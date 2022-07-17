<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Laravel;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use KootLabs\TelegramBotDialogs\DialogManager;
use KootLabs\TelegramBotDialogs\Laravel\Storages\RedisStorageAdapter;
use Psr\SimpleCache\CacheInterface;

final class DialogsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /** @inheritDoc */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/telegramdialogs.php', 'telegramdialogs');

        $this->offerPublishing();
        $this->registerBindings();
    }

    /** Setup the resource publishing groups. */
    private function offerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/config/telegramdialogs.php' => config_path('telegramdialogs.php'),
            ], 'telegram-config');
        }
    }

    private function registerBindings(): void
    {
        $this->app->singleton('telegram.dialogs.cache', static function (Container $app): CacheInterface {
            $config = $app->get('config');
            $connection = $app->make('redis')->connection($config->get('telegramdialogs.stores.redis.connection'));
            return new RedisStorageAdapter($connection);
        });

        $this->app->bind(DialogManager::class, static function (Container $app): DialogManager {
            return new DialogManager($app->make('telegram.bot'), $app->make('telegram.dialogs.cache'));
        });

        $this->app->alias(DialogManager::class, 'telegram.dialogs');
    }

    /**
     * @inheritDoc
     * @return list<string>
     */
    public function provides(): array
    {
        return [
            DialogManager::class,
            'telegram.dialogs',
            'telegram.dialogs.cache',
        ];
    }
}
