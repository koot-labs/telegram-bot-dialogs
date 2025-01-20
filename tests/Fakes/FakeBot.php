<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Tests\Fakes;

use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Telegram\Bot\Api;
use Telegram\Bot\HttpClients\GuzzleHttpClient;
use tests\BotResponse;
use Tests\Fakes\BotHistory;

trait FakeBot
{
    use FakeHttp;

    private BotHistory $botHistory;

    protected function createBotWithQueuedResponse(): Api
    {
        $this->botHistory = new BotHistory();
        BotResponse::resetMessageIds();

        $handler = $this->createHandler();
        $client = new Client(['handler' => $handler]);

        return new class('fake', false, new GuzzleHttpClient($client)) extends Api {
            public function sendChatAction(array $params): bool
            {
                return true;
            }

            public function answerCallbackQuery(array $params): bool
            {
                return true;
            }
        };
    }

    private function createHandler(): mixed
    {
        $handler = $this->createHandlerStack();

        // Track requests
        $handler->push(Middleware::mapRequest(function (RequestInterface $request): RequestInterface {
            /** @var string */
            $body = $request->getBody()->getContents();
            $request->getBody()->rewind();
            $requestData = json_decode($body, true);

            $botRequest = new BotRequest($requestData);
            $this->botHistory->addRequest($botRequest);

            return $request;
        }));

        // Generate responses
        $handler->push(function (callable $handler): callable {
            return function (RequestInterface $request, array $options) use ($handler) {
                /** @var string */
                $body = $request->getBody()->getContents();
                $request->getBody()->rewind();
                $requestData = json_decode($body, true);

                $botRequest = new BotRequest($requestData);
                $response = match (true) {
                    $botRequest->isMessage() => BotResponse::forMessage(),
                    $botRequest->isAction() => BotResponse::forAction(),
                    $botRequest->isCallback() => BotResponse::forCallback(),
                    default => BotResponse::default()
                };

                return $handler($request, $options)->then(
                    function () use ($response) {
                        return $response;
                    }
                );
            };
        });

        return $handler;
    }

    protected function assertBotSentMessage(int $chatId, string $expectedText): void
    {
        $this->botHistory->assertSentMessage($chatId, $expectedText);
    }

    protected function assertBotSentKeyboard(int $chatId, array $buttons): void
    {
        $this->botHistory->assertSentKeyboard($chatId, $buttons);
    }

    protected function assertBotAnsweredCallback(string $callbackQueryId): void
    {
        $this->botHistory->assertAnsweredCallback($callbackQueryId);
    }

    protected function assertBotSentAction(int $chatId, string $action): void
    {
        $this->botHistory->assertSentAction($chatId, $action);
    }

    protected function assertBotSentMessageWithKeyboard(int $chatId, string $text, array $buttons): void
    {
        $this->botHistory->assertSentMessageWithKeyboard($chatId, $text, $buttons);
    }

    protected function assertBotSentMessageWithReplyTo(int $chatId, string $text, int $replyToMessageId): void
    {
        $this->botHistory->assertSentMessageWithReplyTo($chatId, $text, $replyToMessageId);
    }

    protected function assertBotSentMessageWithParseMode(int $chatId, string $text, string $parseMode): void
    {
        $this->botHistory->assertSentMessageWithParseMode($chatId, $text, $parseMode);
    }

    protected function getBotHistory(): BotHistory
    {
        return $this->botHistory;
    }
}
