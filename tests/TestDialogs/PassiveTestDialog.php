<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Tests\TestDialogs;

use KootLabs\TelegramBotDialogs\Dialog;
use Telegram\Bot\Objects\Update;

/**
 * An example of Dialog class, which only listens and doesn't send anything, for test purposes.
 * @internal
 * @api
 */
final class PassiveTestDialog extends Dialog
{
    /** @var list<string> List of method to execute. The order defines the sequence */
    protected array $steps = ['step1', 'step2', 'step3'];

    public function step1(Update $update): void
    {
        return;
    }

    public function step2(Update $update): void
    {
        return;
    }

    public function step3(Update $update): void
    {
        $this->jump("step2");
        return;
    }

    /** @inheritDoc */
    protected function afterLastStep(Update $update): void
    {
        throw new \LogicException(__METHOD__." is called for testing purposes.");
    }
}
