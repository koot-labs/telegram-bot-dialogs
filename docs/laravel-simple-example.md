# Laravel simple example

It’s possible to use the package in 2 ways:
1. Start all dialogs using telegram commands (like `/start`)
2. By reacting to specific Messages/Updates

## Reacting to specific Messages/Updates
> [!IMPORTANT]  
> Note, this example uses Artisan command for testing purposes only.
> It’s not recommended to use this approach in production.
> Instead, please use a Controller that listens income webhooks.

```php
// routes/console.php

use Illuminate\Support\Facades\Artisan;
use KootLabs\TelegramBotDialogs\DialogManager;
use KootLabs\TelegramBotDialogs\Dialogs\HelloExampleDialog;
use Telegram\Bot\BotsManager;
use Telegram\Bot\Laravel\Facades\Telegram;

Artisan::command('telegram:test', function (DialogManager $dialogs, BotsManager $botsManager) {
    $updates = Telegram::commandsHandler();
    $updates = is_array($updates) ? $updates : [$updates];

    /** @var \Telegram\Bot\Objects\Update $update */
    foreach ($updates as $update) {
        if ($dialogs->exists($update)) {
            $dialogs->proceed($update);
        } elseif (str_contains($update->getMessage()?->text, 'hello bot')) {
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
})->purpose('Test Telegram Bot and Dialogs');
```
