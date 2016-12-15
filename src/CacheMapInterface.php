<?php
namespace leinonen\DataLoader;

interface CacheMapInterface
{
    /**
     * Returns the given entry from cache with the given key.
     * Returns false if no entry with the key is found.
     *
     * @param mixed $key
     *
     * @return bool|mixed
     */
    public function get($key);

    /**
     * Sets the cache with the given key and value.
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function set($key, $value);

    /**
     * Deletes the cache entry with the given key.
     *
     * @param mixed $key
     */
    public function delete($key);

    /**
     * Clears the cache map.
     */
    public function clear();
}
