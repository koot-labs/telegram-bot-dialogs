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
    /** @var list<non-empty-string|array{name: string, switch: non-empty-string}> List of method to execute. The order defines the sequence */
    protected array $steps = [
        [
            'name' => 'step1',
            'switch' => 'step2',
        ],
        'step2',
        'step3',
        'step4',
    ];

    public function step2(Update $update): void
    {
        $this->switch("step4");
    }

    public function step3(Update $update): void {}

    public function step4(Update $update): void
    {
        throw new \LogicException(__METHOD__ . " is called for testing purposes.");
    }
}
