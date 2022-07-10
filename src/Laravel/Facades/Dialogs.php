<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bot(\KootLabs\TelegramBotDialogs\Bot $bot):Dialogs
 * @mixin \KootLabs\TelegramBotDialogs\DialogManager
 */
final class Dialogs extends Facade
{
    /** Get the registered name of the component. */
    protected static function getFacadeAccessor(): string
    {
        return 'telegram.dialogs';
    }
}
