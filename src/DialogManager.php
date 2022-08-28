<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs;

use KootLabs\TelegramBotDialogs\Exceptions\ControlFlow\SwitchToAnotherDialog;
use Psr\SimpleCache\CacheInterface;
use Telegram\Bot\Bot;
use Telegram\Bot\Objects\Chat;
use Telegram\Bot\Objects\Update;

final class DialogManager
{
    /** Bot instance to use for all API calls. */
    private Bot $bot;

    /** Storage to store Dialog state between requests. */
    private CacheInterface $storage;

    public function __construct(Bot $bot, CacheInterface $storage)
    {
        $this->bot = $bot;
        $this->storage = $storage;
    }

    /** Use non-default Bot for API calls */
    public function bot(Bot $bot): self
    {
        $this->bot = $bot;
        return $this;
    }

    /** Use non-default Bot for API calls */
    public function setBot(Bot $bot): void
    {
        $this->bot = $bot;
    }

    /**
     * Activate a new Dialog.
     * to start it - call {@see \KootLabs\TelegramBotDialogs\DialogManager::proceed}
     */
    public function activate(Dialog $dialog): void
    {
        $this->storeDialogState($dialog);
    }

    /**
     * Initiate a new Dialog from server side.
     * Note, a User firstly should start a chat with a bot.
     * @experimental
     */
    public function initiate(Dialog $dialog): void
    {
        $this->activate($dialog);

        $this->proceed(new Update([]));

        $dialog->isEnd()
            ? $this->forgetDialogState($dialog)
            : $this->storeDialogState($dialog);
    }

    /**
     * Run next step of the active Dialog.
     * This is a thin wrapper for {@see \KootLabs\TelegramBotDialogs\Dialog::proceed}
     * to store and restore Dialog state between request-response calls.
     */
    public function proceed(Update $update): void
    {
        $dialog = $this->getDialogInstance($update);
        if ($dialog === null) {
            return;
        }

        try {
            $dialog->proceed($update);
        } catch (SwitchToAnotherDialog $exception) {
            $this->forgetDialogState($dialog);
            $this->activate($exception->nextDialog);
            $this->proceed($update);
            return;
        }

        $dialog->isEnd()
            ? $this->forgetDialogState($dialog)
            : $this->storeDialogState($dialog);
    }

    /** Whether an active Dialog exist for a given Update. */
    public function exists(Update $update): bool
    {
        $chat = $update->getChat();
        if (! $chat instanceof Chat) {
            return false;
        }

        return $this->storage->has((string) $chat->id);
    }

    private function getDialogInstance(Update $update): ?Dialog
    {
        if (! $this->exists($update)) {
            return null;
        }

        $chat = $update->getChat();
        if (! $chat instanceof Chat) {
            return null;
        }

        $dialog = $this->readDialogState($chat->id);
        $dialog->setBot($this->bot);

        return $dialog;
    }

    /** Forget Dialog state. */
    private function forgetDialogState(Dialog $dialog): void
    {
        $this->storage->delete((string) $dialog->getChatId());
    }

    /** Store all Dialog. */
    private function storeDialogState(Dialog $dialog): void
    {
        $this->storage->set((string) $dialog->getChatId(), $dialog, $dialog->ttl());
    }

    /** Restore Dialog. */
    private function readDialogState(int $chatId): Dialog
    {
        return $this->storage->get((string) $chatId);
    }
}
