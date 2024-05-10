<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Dialogs;

use KootLabs\TelegramBotDialogs\Dialog;
use Telegram\Bot\Objects\Update;

/**
 * An example of Dialog class for demo purposes.
 * @internal
 * @api
 */
final class HelloExampleDialog extends Dialog
{
    protected array $steps = ['sayHello', 'empathyReply', 'sayBye'];

    public function sayHello(Update $update): void
    {
        $this->bot->sendMessage([
            'chat_id' => $this->getChatId(),
            'text' => 'Hello! How are you?',
        ]);
    }

    public function empathyReply(Update $update): void
    {
        $this->bot->sendMessage([
            'chat_id' => $this->getChatId(),
            'text' => "I’m {$update->message?->text}!",
        ]);
    }

    public function sayBye(Update $update): void
    {
        if ($update->message?->text === 'again') {
            $this->bot->sendMessage([
                'chat_id' => $this->getChatId(),
                'text' => 'OK, send me something — we will start again! 😀',
                'reply_to_message_id' => $update->message->messageId,
            ]);

            $this->jump('sayHello');

            return;
        }

        $this->bot->sendMessage([
            'chat_id' => $this->getChatId(),
            'text' => 'Bye!',
        ]);
    }
}
