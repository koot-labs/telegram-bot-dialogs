<?php

declare(strict_types=1);

namespace KootLabs\TelegramBotDialogs\Storages;

/**
 * Keep exiting methods compatible with {@see \Psr\SimpleCache\CacheInterface} to be able to use CacheInterface instead.
 * @deprecated Will be removed in v1.0. Please use PSR-16 implementations instead. {@see \Psr\SimpleCache\CacheInterface}
 */
interface Store
{
    public const STORE_PREFIX = 'tg:dialog:';

    /**
     * Fetches a value from the cache.
     *
     * @param string $key The unique key of this item in the cache.
     * @param mixed $default Default value to return if the key does not exist.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException MUST be thrown if the $key string is not a legal value.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string $key The key of the item to store.
     * @param mixed $value The value of the item to store, must be serializable.
     * @param null|int|\DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
     *                                    the driver supports TTL then the library may set a default value
     *                                    for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException MUST be thrown if the $key string is not a legal value.
     */
    public function set(string $key, mixed $value, null | int | \DateInterval $ttl = null): bool;

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException MUST be thrown if the $key string is not a legal value.
     */
    public function delete(string $key): bool;

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it making the state of your app out of date.
     *
     * @param string $key The cache item key.
     *
     * @return bool
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException MUST be thrown if the $key string is not a legal value.
     */
    public function has(string $key): bool;
}
