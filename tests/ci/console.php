<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use KootLabs\TelegramBotDialogs\DialogManager;
use KootLabs\TelegramBotDialogs\Dialogs\HelloExampleDialog;
use Telegram\Bot\BotsManager;
use Telegram\Bot\Laravel\Facades\Telegram;

Artisan::command('telegram:dialog:test {ttl=10}', function (DialogManager $dialogs, BotsManager $botsManager, int $ttl): void {
    $this->info("Listening for Telegram Bot updates for {$ttl} seconds...");

    $end = microtime(true) + $ttl;

    while (microtime(true) < $end) {
        $updates = Telegram::commandsHandler();
        $updates = is_array($updates) ? $updates : [$updates];

        /** @var \Telegram\Bot\Objects\Update $update */
        foreach ($updates as $update) {
            // 1. continue active Dialog (if any)
            if ($dialogs->hasActiveDialog($update)) {
                $dialogs->processUpdate($update);
                continue;
            }

            $chatId = $update->getChat()->get('id');
            $message = $update->getMessage();

            // 2. start new Dialog (if activation triggered)
            if ($message instanceof \Telegram\Bot\Objects\Message && ($message->text === '/start' || str_contains($message->text, 'hello bot'))) {
                $dialog = new HelloExampleDialog($chatId);
                $dialogs->activate($dialog);
                $dialogs->processUpdate($update);
                continue;
            }

            // 3. send fallback message (it throws an exception if a user blocked/kicked the bot)
            $botsManager->sendMessage([ // fallback message
                'chat_id' => $chatId,
                'text' => 'There is no active dialog at this moment. You can also start a new dialog by typing <code>hello bot</code> in the chat.',
                'parse_mode' => 'HTML',
            ]);
        }

        // Sleep for 0.2 seconds to prevent the loop from running too fast
        usleep(200_000);
    }

    $this->info("Finished listening for Telegram Bot updates.");
})->purpose('Test Telegram Bot and Dialogs');
