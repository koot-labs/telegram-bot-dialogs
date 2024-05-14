<?php

use Illuminate\Support\Facades\Artisan;
use KootLabs\TelegramBotDialogs\DialogManager;
use KootLabs\TelegramBotDialogs\Dialogs\HelloExampleDialog;
use Telegram\Bot\BotsManager;
use Telegram\Bot\Laravel\Facades\Telegram;

Artisan::command('telegram:dialog:test {ttl=10}', function (DialogManager $dialogs, BotsManager $botsManager, int $ttl): void {
    $this->info("Listening for Telegram Bot updates for $ttl seconds...");

    $end = microtime(true) + $ttl;

    while(microtime(true) < $end) {
        $updates = Telegram::commandsHandler();
        $updates = is_array($updates) ? $updates : [$updates];

        /** @var \Telegram\Bot\Objects\Update $update */
        foreach ($updates as $update) {
            if ($dialogs->exists($update)) {
                $dialogs->proceed($update);
            } elseif (str_contains($update->getMessage()?->text, 'hello bot') || $update->getMessage()?->text === '/start') {
                $dialog = new HelloExampleDialog($update->getChat()->id);
                $dialogs->activate($dialog);
                $dialogs->proceed($update);
            } else {
                $botsManager->sendMessage([ // fallback message
                    'chat_id' => $update->getChat()->id,
                    'text' => 'There is no active dialog at this moment. You can also start a new dialog by typing "hello bot" in the chat.',
                ]);
            }
        }

        // Sleep for 0.2 seconds to prevent the loop from running too fast
        usleep(200_000);
    }

    $this->info("Finished listening for Telegram Bot updates.");
})->purpose('Test Telegram Bot and Dialogs');
