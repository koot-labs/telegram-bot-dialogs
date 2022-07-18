<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Exceptions\ControlFlow;

use KootLabs\TelegramBotDialogs\Dialog;

/** @internal */
final class SwitchToAnotherDialog extends \LogicException implements DialogControlFlowException
{
    public ?Dialog $nextDialog = null;

    private function __construct(Dialog $nextDialog)
    {
        $this->nextDialog = $nextDialog;
        parent::__construct();
    }

    public static function to(Dialog $nextDialog): self
    {
        return new self($nextDialog);
    }
}
