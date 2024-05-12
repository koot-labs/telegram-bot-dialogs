<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Laravel;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use KootLabs\TelegramBotDialogs\DialogManager;

/** @api */
final class DialogsServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /** @inheritDoc */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/telegramdialogs.php', 'telegramdialogs');

        $this->offerPublishing();
        $this->registerBindings();
    }

    /** Setup the resource publishing groups. */
    private function offerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/telegramdialogs.php' => config_path('telegramdialogs.php'),
            ], 'telegram-config');
        }
    }

    private function registerBindings(): void
    {
        $this->app->when(DialogManager::class)
            ->needs(\Psr\SimpleCache\CacheInterface::class)
            ->give(function (Container $app): \Illuminate\Contracts\Cache\Repository {
                $config = $app->make('config');
                $store = $app->make('cache')->store($config->get('telegramdialogs.cache_store'));
                assert($store instanceof \Illuminate\Contracts\Cache\Repository);

                // @todo Find a way to set a custom cache prefix for the store (default is tg:dialog:). E.g.  create DialogRepository class that will have $store as dependency
                // $prefix = $config->get('telegramdialogs.cache_prefix');
                // if (is_string($prefix) && $prefix !== '' && method_exists($store, 'setPrefix')) {
                //    $store->setPrefix($prefix);
                // }

                $tags = $config->get('telegramdialogs.cache_tag');
                if ($tags !== '' && $tags !== [] && method_exists($store, 'tags')) {
                    return $store->tags($config->get('telegramdialogs.cache_tag'));
                }

                return $store;
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
            'telegram.dialogs',
            DialogManager::class,
        ];
    }
}
