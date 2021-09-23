<?php

declare(strict_types=1);

namespace leinonen\DataLoader;

interface CacheMapInterface
{
    /**
     * Returns the given entry from cache with the given key.
     * Returns false if no entry with the key is found.
     *
     * @param  mixed  $key
     * @return bool|mixed
     */
    public function get($key);

    /**
     * Sets the cache with the given key and value.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return void
     */
    public function set($key, $value): void;

    /**
     * Deletes the cache entry with the given key.
     *
     * @param  mixed  $key
     * @return void
     */
    public function delete($key): void;

    /**
     * Clears the cache map.
     *
     * @return void
     */
    public function clear();
}
