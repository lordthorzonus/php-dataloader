<?php declare(strict_types=1);

namespace leinonen\DataLoader;

use React\Promise\ExtendedPromiseInterface;
use React\Promise\Promise;


interface DataLoaderInterface
{
    /**
     * Returns a Promise for the value represented by the given key.
     *
     * @param mixed $key
     *
     * @return ExtendedPromiseInterface
     * @throws \InvalidArgumentException
     */
    public function load($key): ExtendedPromiseInterface;

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
     * @return ExtendedPromiseInterface
     * @throws \InvalidArgumentException
     */
    public function loadMany(array $keys): ExtendedPromiseInterface;

    /**
     * Clears the value for the given key from the cache if it exists. Returns itself for method chaining.
     *
     * @param int|string $key
     *
     * @return void
     */
    public function clear($key): void;

    /**
     * Clears the entire cache. Returns itself for method chaining.
     *
     * @return void
     */
    public function clearAll(): void;

    /**
     * Adds the given key and value to the cache. If the key already exists no change is made.
     * Returns itself for method chaining.
     *
     * @param int|string $key
     * @param int|string $value
     *
     * @return void
     */
    public function prime($key, $value): void;
}
