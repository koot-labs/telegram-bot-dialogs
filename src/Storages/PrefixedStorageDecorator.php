<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Storages;

/** A simple decorator with ability to set a prefix for keys. */
final class PrefixedStorageDecorator implements Storage
{
    private string $prefix;

    private Storage $driver;

    public function __construct(Storage $driver, string $prefix = '')
    {
        $this->driver = $driver;
        $this->prefix = $prefix;
    }

    /** Get prefix of the Storage. */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /** Set Storage prefix to avoid key collisions. */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /** @inheritDoc */
    public function set(string | int $key, mixed $value, int $ttl): void
    {
        $this->driver->set($this->decorateKey($key), $value, $ttl);
    }

    /** @inheritDoc */
    public function get(string | int $key): mixed
    {
        return $this->driver->get($this->decorateKey($key));
    }

    /** @inheritDoc */
    public function has(string | int $key): bool
    {
        return $this->driver->has($this->decorateKey($key));
    }

    /** @inheritDoc */
    public function delete(string | int $key): void
    {
        $this->driver->delete($this->decorateKey($key));
    }

    private function decorateKey(string | int $key): string
    {
        return sprintf('%s%s', $this->prefix, $key);
    }
}
