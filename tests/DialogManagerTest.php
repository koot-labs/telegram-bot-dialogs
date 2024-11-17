<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Tests;

use KootLabs\TelegramBotDialogs\DialogManager;
use KootLabs\TelegramBotDialogs\DialogRepository;
use KootLabs\TelegramBotDialogs\Dialogs\HelloExampleDialog;
use KootLabs\TelegramBotDialogs\Tests\TestDialogs\PassiveTestDialog;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

#[CoversClass(\KootLabs\TelegramBotDialogs\DialogManager::class)]
#[UsesClass(\KootLabs\TelegramBotDialogs\Dialog::class)]
#[UsesClass(\KootLabs\TelegramBotDialogs\DialogRepository::class)]
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

    #[Test]
    #[DataProvider('privateChatUpdatesWithExtractableChatIdProvider')]
    public function it_founds_dialog_for_appropriate_private_chat_updates(string $fixtureFilePathForFollowingUpUpdate): void
    {
        $dialogManager = $this->instantiateDialogManager();
        $initialUpdate = $this->createUpdateFromFixture('private/message--text.json');
        $followingUpUpdate = $this->createUpdateFromFixture($fixtureFilePathForFollowingUpUpdate);
        $dialog = new PassiveTestDialog($initialUpdate->getChat()->id);
        $dialogManager->activate($dialog);

        $dialogIsFound = $dialogManager->exists($followingUpUpdate);

        $this->assertTrue($dialogIsFound);
    }

    /** @return \Generator<non-empty-string, array{0: non-empty-string}> */
    public static function privateChatUpdatesWithExtractableChatIdProvider(): \Generator
    {
        yield 'callback_query' => ['private/callback_query--data.json'];
        yield 'edited_message' => ['private/edited_message.json'];
        yield 'animation' => ['private/message--animation.json'];
        yield 'article-chosen' => ['private/message--article-chosen.json'];
        yield 'audio' => ['private/message--audio.json'];
        yield 'command' => ['private/message--command.json'];
        yield 'contact' => ['private/message--contact.json'];
        yield 'document' => ['private/message--document.json'];
        yield 'emoji' => ['private/message--emoji.json'];
        yield 'location' => ['private/message--location.json'];
        yield 'location_live_0' => ['private/message--location_live_0.json'];
        yield 'location_live_1' => ['private/message--location_live_1.json'];
        yield 'photo' => ['private/message--photo.json'];
        yield 'poll' => ['private/message--poll.json'];
        yield 'sticker' => ['private/message--sticker.json'];
        yield 'text' => ['private/message--text.json'];
        yield 'reply' => ['private/message--text-with-reply.json'];
        yield 'venue' => ['private/message--venue.json'];
        yield 'video' => ['private/message--video.json'];
        yield 'video_note' => ['private/message--video_note.json'];
        yield 'voice' => ['private/message--voice.json'];
        yield 'bot-restarted' => ['private/my_chat_member--bot-restarted.json'];
        yield 'bot-kicked' => ['private/my_chat_member--kicked.json'];
        yield 'bot-unkicked' => ['private/my_chat_member--unkicked.json'];
    }

    private function createUpdateFromFixture(string $relativeFilepath): Update
    {
        $fixture = file_get_contents(__DIR__ . "/_fixtures/{$relativeFilepath}");
        $fixture = json_decode($fixture, true, 512, \JSON_THROW_ON_ERROR);

        return new Update($fixture);
    }
}
