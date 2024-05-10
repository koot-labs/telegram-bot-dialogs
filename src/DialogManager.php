<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs;

use KootLabs\TelegramBotDialogs\Storages\Store;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

final class DialogManager
{
    /** Bot instance to use for all API calls. */
    private Api $bot;

    /** Storage to store Dialog state between requests. */
    private Store $store;

    public function __construct(Api $bot, Store $store)
    {
        $this->bot = $bot;
        $this->store = $store;
    }

    /**
     * Activate a new Dialog.
     * to start it - call {@see \KootLabs\TelegramBotDialogs\DialogManager::proceed}
     */
    public function activate(Dialog $dialog): void
    {
        $this->storeDialogState($dialog);
    }

    /** Use non-default Bot for API calls */
    public function setBot(Api $bot): void
    {
        $this->bot = $bot;
    }

    private function getDialogInstance(Update $update): ?Dialog
    {
        $storeDialogKey = $this->findDialogKeyForStore($update);
        if ($storeDialogKey === null) {
            return null;
        }

        $dialog = $this->readDialogState($storeDialogKey);
        $dialog->setBot($this->bot);

        return $dialog;
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

        $dialog->proceed($update);

        if ($dialog->isEnd()) {
            $this->forgetDialogState($dialog);
        } else {
            $this->storeDialogState($dialog);
        }
    }

    /** @return non-empty-string|null */
    private function findDialogKeyForStore(Update $update): ?string
    {
        $sharedDialogKey = $this->generateDialogKeySharedBetweenUsers($update);
        if ($this->store->has($sharedDialogKey)) {
            return $sharedDialogKey;
        }

        $userBoundedDialogKey = $this->generateDialogKeyUserBounded($update);
        if ($this->store->has($userBoundedDialogKey)) {
            return $userBoundedDialogKey;
        }

        return null;
    }

    /** Whether an active Dialog exist for a given Update. */
    public function exists(Update $update): bool
    {
        return is_string($this->findDialogKeyForStore($update));
    }

    /** Forget Dialog state. */
    private function forgetDialogState(Dialog $dialog): void
    {
        $this->store->delete($this->getDialogKey($dialog));
    }

    /** Store all Dialog. */
    private function storeDialogState(Dialog $dialog): void
    {
        $this->store->set($this->getDialogKey($dialog), $dialog, $dialog->ttl());
    }

    /** Restore Dialog. */
    private function readDialogState(string $key): Dialog
    {
        return $this->store->get($key);
    }

    /** @internal This method is a subject for changes in further releases < 1.0 */
    private function generateDialogKeyUserBounded(Update $update): string
    {
        return implode('-', [
            $update->getMessage()->from->id,
            $update->getChat()->id,
        ]);
    }

    /** @internal This method is a subject for changes in further releases < 1.0 */
    private function generateDialogKeySharedBetweenUsers(Update $update): string
    {
        return implode('-', [
            $update->getChat()->id,
        ]);
    }

    /** @internal This method is a subject for changes in further releases < 1.0 */
    private function getDialogKey(Dialog $dialog): string
    {
        return implode('-', array_filter([
            $dialog->getChatId(),
            $dialog->getUserId(),
        ]));
    }
}
