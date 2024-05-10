<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @api
 * @method static \KootLabs\TelegramBotDialogs\DialogManager setBot(\Telegram\Bot\Api $bot)
 * @method static void activate(\KootLabs\TelegramBotDialogs\Dialog $dialog)
 * @method static void proceed(\Telegram\Bot\Objects\Update $update)
 */
final class Dialogs extends Facade
{
    /** Get the registered name of the component. */
    protected static function getFacadeAccessor(): string
    {
        return 'telegram.dialogs';
    }
}
