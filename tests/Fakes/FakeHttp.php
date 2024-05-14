<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Tests\Fakes;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Telegram\Bot\HttpClients\GuzzleHttpClient;

trait FakeHttp
{
    /**
     * This collection contains a history of all requests and responses
     * sent using the client.
     */
    protected array $history = [];

    public function getGuzzleHttpClient(array $responsesToQueue = []): GuzzleHttpClient
    {
        $client = $this->createClientWithQueuedResponse($responsesToQueue);

        return new GuzzleHttpClient($client);
    }

    protected function createClientWithQueuedResponse(array $responsesToQueue): Client
    {
        $this->history = [];
        $handler = HandlerStack::create(new MockHandler($responsesToQueue));
        $handler->push(Middleware::history($this->history));

        return new Client(['handler' => $handler]);
    }

    public function makeFakeServerResponse(array $data, int $statusCode = 200, array $headers = []): Response
    {
        return new Response(
            $statusCode,
            $headers,
            json_encode([
                'ok' => true,
                'result' => $data,
            ], \JSON_THROW_ON_ERROR)
        );
    }
}