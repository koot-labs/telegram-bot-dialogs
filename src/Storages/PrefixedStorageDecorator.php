<?php declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Storages;

use Psr\SimpleCache\CacheInterface;

/** A simple decorator with ability to set a prefix for keys. */
class PrefixedStorageDecorator implements CacheInterface
{
    private string $prefix;

    private CacheInterface $driver;

    public function __construct(CacheInterface $driver, string $prefix = '')
    {
        $this->driver = $driver;
        $this->prefix = $prefix;
    }

    /** @inheritDoc */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->driver->get($this->decorateKey($key), $default);
    }

    /** @inheritDoc */
    public function set(string $key, mixed $value, null | int | \DateInterval $ttl = null): bool
    {
        return $this->driver->set($this->decorateKey($key), $value, $ttl);
    }

    /** @inheritDoc */
    public function delete(string $key): bool
    {
        return $this->driver->delete($this->decorateKey($key));
    }

    /** @inheritDoc */
    public function clear(): bool
    {
        return $this->driver->clear();
    }

    /** @inheritDoc */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $prefixedKeys = array_map(fn ($key) => $this->decorateKey($key), $keys);
        return $this->driver->getMultiple($prefixedKeys, $default);
    }

    /** @inheritDoc */
    public function setMultiple(iterable $values, \DateInterval | int | null $ttl = null): bool
    {
        $valuesWihPrefixedKeys = [];
        foreach ($values as $key => $value) {
            $valuesWihPrefixedKeys[$this->decorateKey($key)] = $value;
        }

        return $this->driver->setMultiple($valuesWihPrefixedKeys, $ttl);
    }

    /** @inheritDoc */
    public function deleteMultiple(iterable $keys): bool
    {
        $prefixedKeys = array_map(fn ($key) => $this->decorateKey($key), $keys);
        return $this->driver->deleteMultiple($prefixedKeys);
    }

    /** @inheritDoc */
    public function has(string $key): bool
    {
        return $this->driver->has($this->decorateKey($key));
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

    protected function decorateKey(string | int $key): string
    {
        return sprintf('%s%s', $this->prefix, $key);
    }
}
