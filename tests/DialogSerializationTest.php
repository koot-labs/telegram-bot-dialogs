<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Tests;

use KootLabs\TelegramBotDialogs\Dialogs\HelloExampleDialog;
use KootLabs\TelegramBotDialogs\Tests\Fakes\FakeBot;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Telegram\Bot\Objects\Update;

#[CoversClass(\KootLabs\TelegramBotDialogs\Dialog::class)]
final class DialogSerializationTest extends TestCase
{
    use FakeBot;

    private const RANDOM_CHAT_ID = 42;

    #[Test]
    public function it_can_be_serialized_and_unserialized_on_the_first_step(): void
    {
        $bot = $this->createBotWithQueuedResponse();
        $dialog = new HelloExampleDialog(self::RANDOM_CHAT_ID, $bot);

        $unserializedDialog = unserialize(serialize($dialog));

        $this->assertSame($dialog->getChatId(), $unserializedDialog->getChatId());
        $this->assertSame($dialog->getUserId(), $unserializedDialog->getUserId());
        $this->assertSame($dialog->ttl(), $unserializedDialog->ttl());
        $this->assertSame($dialog->isStart(), $unserializedDialog->isStart());
    }

    #[Test]
    public function it_can_be_serialized_and_unserialized_on_step_after_first(): void
    {
        $bot = $this->createBotWithQueuedResponse();
        $dialog = new HelloExampleDialog(self::RANDOM_CHAT_ID, $bot);
        $dialog->proceed(new Update([]));

        $unserializedDialog = unserialize(serialize($dialog));

        $this->assertSame($dialog->getChatId(), $unserializedDialog->getChatId());
        $this->assertSame($dialog->getUserId(), $unserializedDialog->getUserId());
        $this->assertSame($dialog->ttl(), $unserializedDialog->ttl());
        $this->assertSame($dialog->isStart(), $unserializedDialog->isStart());
    }
}
