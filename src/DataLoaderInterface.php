<?php

namespace leinonen\DataLoader;

use React\Promise\Promise;

interface DataLoaderInterface
{
    /**
     * Returns a Promise for the value represented by the given key.
     *
     * @param mixed $key
     *
     * @return Promise
     * @throws \InvalidArgumentException
     */
    public function load($key);

    /**
     * Loads multiple keys, promising an array of values.
     *
     * This is equivalent to the more verbose:
     *
     *  \React\Promise\all([
     *      $dataLoader->load('a');
     *      $dataLoader->load('b');
     *  });
     *
     * @param array $keys
     *
     * @return Promise
     * @throws \InvalidArgumentException
     */
    public function loadMany(array $keys);

    /**
     * Clears the value for the given key from the cache if it exists. Returns itself for method chaining.
     *
     * @param int|string $key
     *
     * @return $this
     */
    public function clear($key);

    /**
     * Clears the entire cache. Returns itself for method chaining.
     *
     * @return $this
     */
    public function clearAll();

    /**
     * Adds the given key and value to the cache. If the key already exists no change is made.
     * Returns itself for method chaining.
     *
     * @param int|string $key
     * @param int|string $value
     *
     * @return $this
     */
    public function prime($key, $value);
}
