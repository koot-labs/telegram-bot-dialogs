<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Dialogs;

use KootLabs\TelegramBotDialogs\Dialog;
use KootLabs\TelegramBotDialogs\Exceptions\UnexpectedUpdateType;
use Telegram\Bot\Actions;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Objects\Update;

/**
 * An example of Dialog class for demo purposes.
 * @internal
 * @api
 */
final class HelloExampleDialog extends Dialog
{
    /** @var list<string> List of method to execute. The order defines the sequence */
    protected array $steps = ['sayHello', 'empathyReply', 'sayBye'];

    public function sayHello(Update $update): void
    {
        $moodKeyboard = Keyboard::make()->inline()->row([
            // https://core.telegram.org/bots/api#inlinekeyboardbutton
            ['text' => 'Awesome 🤩', 'callback_data' => 'MOOD:awesome'],
            ['text' => 'Great 😀', 'callback_data' => 'MOOD:great'],
            ['text' => 'Good 🙂', 'callback_data' => 'MOOD:good'],
            ['text' => 'Bad ☹️', 'callback_data' => 'MOOD:bad'],
        ]);

        $this->bot->sendMessage([
            'chat_id' => $this->getChatId(),
            'text' => "👋 {$update->message?->from?->firstName}! I’m a Dialog bot. How are you today?",
            'reply_markup' => $moodKeyboard,
        ]);
    }

    public function empathyReply(Update $update): void
    {
        $callbackQuery = $update->callbackQuery;
        if (! $callbackQuery instanceof CallbackQuery || !str_starts_with($callbackQuery->data, 'MOOD:')) {
            $this->bot->sendMessage([
                'chat_id' => $this->getChatId(),
                'text' => 'Please answer the question by selecting one of the options from the inline keyboard above.',
            ]);
            // repeat step again
            throw new UnexpectedUpdateType('callbackQuery expected');
        }

        $this->bot->answerCallbackQuery(['callback_query_id' => $update->callbackQuery->id, 'cache_time' => 2]);

        $userMood = mb_substr($update->callbackQuery->data, mb_strlen('MOOD:'));
        $this->memory->put('userMood', $userMood);

        $this->bot->sendMessage([
            'chat_id' => $this->getChatId(),
            'text' => "I’m also doing <b>{$userMood}</b> today!",
            'parse_mode' => 'HTML',
            'reply_to_message_id' => $update->message?->messageId,
        ]);

        $this->bot->sendChatAction([
            'chat_id' => $this->getChatId(),
            'action' => Actions::TYPING,
        ]);

        $this->bot->sendMessage([
            'chat_id' => $this->getChatId(),
            'parse_mode' => 'HTML',
            'text' => "Do you want to start again? Just type <code>again</code>, or send me anything else to finish the dialog!",
        ]);
    }

    public function sayBye(Update $update): void
    {
        if ($update->message?->text === 'again') {
            $this->bot->sendMessage([
                'chat_id' => $this->getChatId(),
                'text' => "OK, send me something — we will try to improve your {$this->memory->get('userMood', 'awesome')} mood! 😀",
                'reply_to_message_id' => $update->message?->messageId,
            ]);

            $this->nextStep('sayHello');

            return;
        }

        $this->bot->sendMessage([
            'chat_id' => $this->getChatId(),
            'parse_mode' => 'HTML',
            'text' => "Bye!\n\nPS: Please do not forget to star <a href='https://github.com/koot-labs/telegram-bot-dialogs'>koot-labs-telegram-bot-dialogs</a> if you like this library! ⭐️",
        ]);
    }
}
