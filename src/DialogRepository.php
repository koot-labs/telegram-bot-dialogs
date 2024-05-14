<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs;

use Psr\SimpleCache\CacheInterface;

/** @api */
final class DialogRepository
{
    private CacheInterface $cache;
    private string $prefix;

    public function __construct(CacheInterface $cache, string $prefix = '')
    {
        $this->cache = $cache;
        $this->prefix = $prefix;
    }

    public function put(string $key, Dialog $dialog, \DateTime | int $seconds): void
    {
        $this->cache->set($this->prefixKey($key), serialize($dialog), $seconds);
    }

    public function has(string $key): bool
    {
        return $this->cache->has($this->prefixKey($key));
    }

    public function get(string $key): Dialog
    {
        return unserialize($this->cache->get($this->prefixKey($key)), ['allowed_classes' => true]);
    }

    public function forget(string $key): bool
    {
        return $this->cache->delete($this->prefixKey($key));
    }

    private function prefixKey(string $key): string
    {
        return "{$this->prefix}{$key}";
    }
}
