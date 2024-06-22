<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Tests;

use KootLabs\TelegramBotDialogs\DialogManager;
use KootLabs\TelegramBotDialogs\DialogRepository;
use KootLabs\TelegramBotDialogs\Dialogs\HelloExampleDialog;
use KootLabs\TelegramBotDialogs\Tests\TestDialogs\PassiveTestDialog;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

#[CoversClass(\KootLabs\TelegramBotDialogs\DialogManager::class)]
final class DialogManagerTest extends TestCase
{
    private const RANDOM_CHAT_ID = 42;
    private const RANDOM_USER_ID = 110;

    private function instantiateDialogManager(): DialogManager
    {
        return new DialogManager(
            new Api('fake-token'),
            new DialogRepository(new Psr16Cache(new ArrayAdapter()), 'some_prefix_'), // use array/in-memory store
        );
    }

    #[Test]
    public function it_finds_an_activated_dialog(): void
    {
        $dialogManager = $this->instantiateDialogManager();
        $dialog = new HelloExampleDialog(self::RANDOM_CHAT_ID);

        $dialogManager->activate($dialog);
        $exist = $dialogManager->exists(new Update(['message' => ['chat' => ['id' => self::RANDOM_CHAT_ID]]]));

        $this->assertTrue($exist);
    }

    #[Test]
    public function it_finds_a_user_bounded_activated_dialog(): void
    {
        $dialogManager = $this->instantiateDialogManager();
        $dialog = new HelloExampleDialog(self::RANDOM_CHAT_ID, null, self::RANDOM_USER_ID);

        $dialogManager->activate($dialog);
        $existResult = $dialogManager->exists(new Update(['message' => [
            'chat' => ['id' => self::RANDOM_CHAT_ID],
            'from' => ['id' => self::RANDOM_USER_ID],
        ]]));

        $this->assertTrue($existResult);
    }

    #[Test]
    public function it_do_not_find_dialog_if_it_is_not_activated(): void
    {
        $dialogManager = $this->instantiateDialogManager();

        $existResult = $dialogManager->exists(new Update(['message' => [
            'chat' => ['id' => self::RANDOM_CHAT_ID],
            'from' => ['id' => self::RANDOM_USER_ID],
        ]]));

        $this->assertFalse($existResult);
    }

    #[Test]
    public function it_do_not_find_dialog_if_it_ts_not_activated_for_the_current_chat(): void
    {
        $dialogManager = $this->instantiateDialogManager();
        $dialog = new HelloExampleDialog(self::RANDOM_CHAT_ID);
        $dialogManager->activate($dialog);

        $existResult = $dialogManager->exists(new Update(['message' => [
            'chat' => ['id' => 43],
            'from' => ['id' => self::RANDOM_USER_ID]],
        ]));

        $this->assertFalse($existResult);
    }

    #[Test]
    public function it_throws_custom_exception_when_switch_to_another_step(): void
    {
        $dialogManager = $this->instantiateDialogManager();
        $dialog = new PassiveTestDialog(self::RANDOM_CHAT_ID);

        $dialogManager->activate($dialog);
        $this->expectException(\LogicException::class);
        $dialog->proceed(new Update(['message' => ['chat' => ['id' => self::RANDOM_CHAT_ID]]]));
    }
}
