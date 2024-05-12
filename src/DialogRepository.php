<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs;

use Psr\SimpleCache\CacheInterface;

/** @api */
final class DialogRepository
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function put(string $key, Dialog $dialog, \DateTime | int $seconds): void
    {
        $this->cache->set($key, serialize($dialog), $seconds);
    }

    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    public function get(string $key): Dialog
    {
        return unserialize($this->cache->get($key), ['allowed_classes' => true]);
    }

    public function forget(string $key): bool
    {
        return $this->cache->delete($key);
    }
}
