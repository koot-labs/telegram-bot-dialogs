<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Tests\Fakes;

use Telegram\Bot\Api;

trait FakeBot
{
    use FakeHttp;

    public function createBotWithQueuedResponse(array $data = [], int $statusCode = 200, array $headers = []): Api
    {
        return new Api(
            'fake',
            false,
            $this->getGuzzleHttpClient([$this->makeFakeServerResponse($data, $statusCode, $headers)])
        );
    }
}
