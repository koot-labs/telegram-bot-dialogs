<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @api
 * @method static void activate(\KootLabs\TelegramBotDialogs\Dialog $dialog) Activate a given Dialog, so processUpdate() will execute it.
 * @method static void processUpdate(\Telegram\Bot\Objects\Update $update) Pass Update into the active Dialog (if any) to process it.
 * @method static \KootLabs\TelegramBotDialogs\DialogManager setBot(\Telegram\Bot\Api $bot) Change the Bot instance to use for API calls.
 * @method static void initiateDialog(array $updateData = []) Initiate a new Dialog from the server side.
 */
final class Dialogs extends Facade
{
    /** Get the registered name of the component. */
    protected static function getFacadeAccessor(): string
    {
        return 'telegram.dialogs';
    }
}
