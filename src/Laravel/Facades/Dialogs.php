<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use KootLabs\TelegramBotDialogs\DialogManager;

/**
 * @method static bot(\Telegram\Bot\Api $bot):Dialogs
 * @mixin \KootLabs\TelegramBotDialogs\DialogManager
 */
final class Dialogs extends Facade
{
    /** Get the registered name of the component. */
    protected static function getFacadeAccessor(): string
    {
        return DialogManager::class;
    }
}
