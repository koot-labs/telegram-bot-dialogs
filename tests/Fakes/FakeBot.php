<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Tests\Fakes;

use Telegram\Bot\Api;

trait FakeBot
{
    use FakeHttp;

    protected function createBotWithQueuedResponse(array $resultData = [], int $statusCode = 200, array $headers = []): Api
    {
        return new Api(
            'fake',
            false,
            $this->getGuzzleHttpClient([
                $this->makeFakeServerResponse($resultData, $statusCode, $headers),
                $this->makeFakeServerResponse($resultData, $statusCode, $headers),
                $this->makeFakeServerResponse($resultData, $statusCode, $headers),
            ])
        );
    }
}
