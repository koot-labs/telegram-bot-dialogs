<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Laravel\Stores;

use Illuminate\Contracts\Redis\Connection;
use KootLabs\TelegramBotDialogs\Storages\Store;

/** @api */
final class RedisStoreAdapter implements Store
{
    private Connection $redis;

    public function __construct(Connection $connection)
    {
        $this->redis = $connection;
    }

    /** @inheritDoc */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis->get($this->decorateKey($key));

        return $value !== null ? $this->unserialize($value) : $default;
    }

    /** @inheritDoc */
    public function set(string $key, mixed $value, null | int | \DateInterval $ttl = null): bool
    {
        $ttl = $ttl === 0 ? -1 : $ttl;
        $this->redis->setEx($this->decorateKey($key), $ttl, $this->serialize($value));
    }

    /** @inheritDoc */
    public function delete(string $key): bool
    {
        $this->redis->del($this->decorateKey($key));
    }

    /** @inheritDoc */
    public function has(string $key): bool
    {
        return (bool) $this->redis->exists($this->decorateKey($key));
    }

    /** Serialize the value. */
    private function serialize(mixed $value): string | int | float
    {
        return is_numeric($value) && !in_array($value, [\INF, -\INF], true) && !is_nan($value)
            ? $value
            : serialize($value);
    }

    /** Unserialize the value. */
    private function unserialize(string | int | float $value): mixed
    {
        return is_numeric($value)
            ? $value
            : unserialize($value, ['allowed_classes' => true]);
    }

    private function decorateKey(string | int $key): string
    {
        return sprintf('%s:%s', self::STORE_PREFIX, $key);
    }
}
