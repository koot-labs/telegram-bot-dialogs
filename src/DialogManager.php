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
        if (! $this->exists($update)) {
            return null;
        }

        $key = $this->generateDialogKey($update);

        $dialog = $this->readDialogState($key);
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
            $this->store->delete($dialog->getDialogKey());
        } else {
            $this->storeDialogState($dialog);
        }
    }

    /** Whether Dialog exist for a given Update. */
    public function exists(Update $update): bool
    {
        $key = $this->generateDialogKey($update);

        return $key && $this->store->has($key);
    }

    /** Store all Dialog. */
    private function storeDialogState(Dialog $dialog): void
    {
        $this->store->set($dialog->getDialogKey(), $dialog, $dialog->ttl());
    }

    /** Restore Dialog. */
    private function readDialogState($key): Dialog
    {
        return $this->store->get($key);
    }

    private function generateDialogKey(Update $update): string
    {
        return implode('-', [$update->getMessage()->from->id,  $update->getChat()->id]);
    }
}
