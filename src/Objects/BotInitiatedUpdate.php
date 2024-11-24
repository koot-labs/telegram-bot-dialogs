<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Objects;

use KootLabs\TelegramBotDialogs\Dialog;
use Telegram\Bot\Objects\Update;

/**
 * @api
 * @experimental This class is experimental and may be removed anytime.
 */
final class BotInitiatedUpdate extends Update
{
    public Dialog $dialog;

    /**
     * @param \KootLabs\TelegramBotDialogs\Dialog $dialog
     * @param array<mixed> $updateData Raw Update data to create {@see \Telegram\Bot\Objects\Update} instance from.
     */
    public function __construct(Dialog $dialog, array $updateData = [])
    {
        $this->dialog = $dialog;

        parent::__construct($updateData);
    }
}
