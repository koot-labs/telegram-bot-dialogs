<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs;

use KootLabs\TelegramBotDialogs\Exceptions\ControlFlow\SwitchToAnotherDialog;
use KootLabs\TelegramBotDialogs\Objects\BotInitiatedUpdate;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

/** @api */
final class DialogManager
{
    /** Bot instance to use for all API calls. */
    private Api $bot;

    /** Storage to store Dialog state between requests. */
    private DialogRepository $repository;

    public function __construct(Api $bot, DialogRepository $repository)
    {
        $this->bot = $bot;
        $this->repository = $repository;
    }

    /** @api Use non-default Bot for API calls */
    public function setBot(Api $bot): self
    {
        $this->bot = $bot;
        return $this;
    }

    /**
     * @api Activate a new Dialog.
     * to start it - call {@see \KootLabs\TelegramBotDialogs\DialogManager::proceed}
     */
    public function activate(Dialog $dialog): void
    {
        $this->storeDialogState($dialog);
    }

    /**
     * Initiate a new Dialog from server side (e.g. by cron).
     * Note, a User firstly should start a chat with a bot (bot can't initiate a chat — this is TG Bot API limitation).
     * @api
     * @experimental
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function startNewDialogInitiatedByBot(Dialog $dialog): void
    {
        $this->activate($dialog);

        $this->proceed(new BotInitiatedUpdate($dialog));

        $dialog->isEnd()
            ? $this->forgetDialogState($dialog)
            : $this->storeDialogState($dialog);
    }

    /**
     * @api
     * Run next step of the active Dialog.
     * This is a thin wrapper for {@see \KootLabs\TelegramBotDialogs\Dialog::proceed}
     * to store and restore Dialog state between request-response calls.
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function proceed(Update $update): void
    {
        $dialog = $this->getDialogInstance($update);
        if (! $dialog instanceof Dialog) {
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
        if ($this->repository->has($sharedDialogKey)) {
            return $sharedDialogKey;
        }

        $userBoundedDialogKey = $this->generateDialogKeyUserBounded($update);
        if ($this->repository->has($userBoundedDialogKey)) {
            return $userBoundedDialogKey;
        }

        return null;
    }

    /** @api Whether an active Dialog exist for a given Update. */
    public function exists(Update $update): bool
    {
        return is_string($this->findDialogKeyForStore($update));
    }

    /** Get instance of the current active Dialog from a Storage. */
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

    /** Forget Dialog state. */
    private function forgetDialogState(Dialog $dialog): void
    {
        $this->repository->forget($this->getDialogKey($dialog));
    }

    /** Store all Dialog. */
    private function storeDialogState(Dialog $dialog): void
    {
        $this->repository->put($this->getDialogKey($dialog), $dialog, $dialog->ttl());
    }

    /** Restore Dialog. */
    private function readDialogState(string $key): Dialog
    {
        return $this->repository->get($key);
    }

    /** @internal This method is a subject for changes in further releases < 1.0 */
    private function generateDialogKeyUserBounded(Update $update): string
    {
        return implode('-', [
            $update->getChat()->id,
            $update->getMessage()->from->id,
        ]);
    }

    /**
     * @internal This method is a subject for changes in further releases < 1.0
     * @return non-empty-string
     */
    private function generateDialogKeySharedBetweenUsers(Update $update): string
    {
        return implode('-', [
            $update->getChat()->id,
        ]);
    }

    /**
     * @internal This method is a subject for changes in further releases < 1.0
     * @return non-empty-string
     */
    private function getDialogKey(Dialog $dialog): string
    {
        return implode('-', array_filter([
            $dialog->getChatId(),
            $dialog->getUserId(),
        ]));
    }
}
