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

    public function __construct(Dialog $dialog, array $data = [])
    {
        $this->dialog = $dialog;

        parent::__construct($data);
    }
}
