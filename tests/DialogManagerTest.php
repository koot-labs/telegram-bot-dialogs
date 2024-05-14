<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Tests;

use KootLabs\TelegramBotDialogs\DialogManager;
use KootLabs\TelegramBotDialogs\Dialogs\HelloExampleDialog;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

#[CoversClass(\KootLabs\TelegramBotDialogs\DialogManager::class)]
final class DialogManagerTest extends TestCase
{
    private function instantiateDialogManager(): DialogManager
    {
        return new DialogManager(
            new Api('fake-token'),
            new Psr16Cache(new ArrayAdapter()), // use array/in-memory store
        );
    }

    #[Test]
    public function it_finds_an_activated_dialog(): void
    {
        $dialogManager = $this->instantiateDialogManager();
        $dialog = new HelloExampleDialog(42);

        $dialogManager->activate($dialog);
        $exist = $dialogManager->exists(new Update(['message' => ['chat' => ['id' => 42]]]));

        $this->assertTrue($exist);
    }

    #[Test]
    public function it_do_not_find_dialog_if_it_ts_not_activated(): void
    {
        $dialogManager = $this->instantiateDialogManager();

        $exist = $dialogManager->exists(new Update(['message' => ['chat' => ['id' => 42]]]));

        $this->assertFalse($exist);
    }

    #[Test]
    public function it_do_not_find_dialog_if_it_ts_not_activated_for_the_current_chat(): void
    {
        $dialogManager = $this->instantiateDialogManager();
        $dialog = new HelloExampleDialog(42);

        $dialogManager->activate($dialog);
        $exist = $dialogManager->exists(new Update(['message' => ['chat' => ['id' => 43]]]));

        $this->assertFalse($exist);
    }
}
