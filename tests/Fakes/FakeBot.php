<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Tests\Fakes;

use Telegram\Bot\Api;

trait FakeBot
{
    use FakeHttp;

    private array $lastSentMessage = [];

    protected function createBotWithQueuedResponse(array $resultData = [], int $statusCode = 200, array $headers = []): Api
    {
        $bot = new class('fake', false, $this->getGuzzleHttpClient([
            $this->makeFakeServerResponse($resultData, $statusCode, $headers),
            $this->makeFakeServerResponse($resultData, $statusCode, $headers),
            $this->makeFakeServerResponse($resultData, $statusCode, $headers),
        ])) extends Api {
            private array $lastSentMessage = [];

            public function sendMessage(array $params): \Telegram\Bot\Objects\Message
            {
                $this->lastSentMessage = $params;
                return parent::sendMessage($params);
            }

            public function getLastSentMessage(): array
            {
                return $this->lastSentMessage;
            }
        };

        return $bot;
    }
}
