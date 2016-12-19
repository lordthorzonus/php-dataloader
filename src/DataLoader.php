<?php

namespace leinonen\DataLoader;

use React\Promise\Promise;
use React\EventLoop\LoopInterface;

class DataLoader
{
    /**
     * @var callable
     */
    private $batchLoadFunction;

    /**
     * @var DataLoaderOptions
     */
    private $options;

    /**
     * @var array
     */
    private $promiseQueue = [];

    /**
     * @var CacheMapInterface
     */
    private $promiseCache;

    /**
     * Initiates a new DataLoader.
     *
     * @param callable $batchLoadFunction The function which will be called for the batch loading.
     * It must Accepts an array of keys and returns a Promise which resolves to an array of values.
     * @param LoopInterface $loop
     * @param CacheMapInterface $cacheMap
     * @param null|DataLoaderOptions $options
     */
    public function __construct(
        callable $batchLoadFunction,
        LoopInterface $loop,
        CacheMapInterface $cacheMap,
        DataLoaderOptions $options = null
    ) {
        $this->batchLoadFunction = $batchLoadFunction;
        $this->eventLoop = $loop;
        $this->promiseCache = $cacheMap;
        $this->options = empty($options) ? new DataLoaderOptions() : $options;
    }

    /**
     * Returns a Promise for the value represented by the given key.
     *
     * @param mixed $key
     *
     * @return Promise
     * @throws \InvalidArgumentException
     */
    public function load($key)
    {
        if ($key === null) {
            throw new \InvalidArgumentException(self::class . '::load must be called with a value, but got null');
        }

        if ($this->options->shouldCache()) {
            if ($this->promiseCache->get($key)) {
                return $this->promiseCache->get($key);
            }
        }

        $promise = new Promise(
            function (callable $resolve, callable $reject) use ($key) {
                $this->promiseQueue[] = [
                    'key' => $key,
                    'resolve' => $resolve,
                    'reject' => $reject,
                ];

                if (count($this->promiseQueue) === 1) {
                    $this->scheduleDispatch();
                }
            }
        );

        if ($this->options->shouldCache()) {
            $this->promiseCache->set($key, $promise);
        }

        return $promise;
    }

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
     */
    public function loadMany(array $keys)
    {
        return \React\Promise\all(
            array_map(
                function ($key) {
                    return $this->load($key);
                },
                $keys
            )
        );
    }

    /**
     * Clears the value for the given key from the cache if it exists. Returns itself for method chaining.
     *
     * @param int|string $key
     *
     * @return $this
     */
    public function clear($key)
    {
        $this->promiseCache->delete($key);

        return $this;
    }

    /**
     * Clears the entire cache. Returns itself for method chaining.
     *
     * @return $this
     */
    public function clearAll()
    {
        $this->promiseCache->clear();

        return $this;
    }

    /**
     * Adds the given key and value to the cache. If the key already exists no change is made.
     * Returns itself for method chaining.
     *
     * @param int|string $key
     * @param int|string $value
     *
     * @return $this
     */
    public function prime($key, $value)
    {
        if (! $this->promiseCache->get($key)) {
            // Cache a rejected promise if the value is an Exception, in order to match
            // the behavior of load($key).
            $promise = $value instanceof \Exception ? \React\Promise\reject($value) : \React\Promise\resolve($value);

            $this->promiseCache->set($key, $promise);
        }

        return $this;
    }

    /**
     * Schedules the dispatch to happen on the next tick of the EventLoop
     * If batching is disabled, schedule the dispatch immediately.
     *
     * @return void
     */
    private function scheduleDispatch()
    {
        if ($this->options->shouldBatch()) {
            $this->eventLoop->nextTick(
                function () {
                    $this->dispatchQueue();
                }
            );

            return;
        }

        $this->dispatchQueue();
    }

    /**
     * Resets and dispatches the DataLoaders queue.
     *
     * @return void
     */
    private function dispatchQueue()
    {
        $queue = $this->promiseQueue;
        $this->promiseQueue = [];

        $maxBatchSize = $this->options->getMaxBatchSize();

        if ($maxBatchSize && $maxBatchSize > 0 && $maxBatchSize < count($queue)) {
            $this->dispatchQueueInMultipleBatches($queue, $maxBatchSize);
        } else {
            $this->dispatchQueueBatch($queue);
        }
    }

    /**
     * Dispatches a batch of a queue. The given batch can also be the whole queue.
     *
     * @param $batch
     *
     * @return void
     */
    private function dispatchQueueBatch($batch)
    {
        $keys = array_column($batch, 'key');

        $batchLoadFunction = $this->batchLoadFunction;
        /** @var Promise $batchPromise */
        $batchPromise = $batchLoadFunction($keys);

        if (! $batchPromise || ! is_callable([$batchPromise, 'then'])) {
            throw new \RuntimeException(
                self::class . ' must be constructed with a function which accepts ' .
                'an array of keys and returns a Promise which resolves to an array of values ' .
                sprintf('not return a Promise: %s.', gettype($batchPromise))
            );
        }

        $batchPromise->then(
            function ($values) use ($batch, $keys) {
                if (! is_array($values)) {
                    $this->handleFailedDispatch($batch, new \RuntimeException(
                        DataLoader::class . ' must be constructed with a function which accepts ' .
                        'an array of keys and returns a Promise which resolves to an array of values ' .
                        sprintf('not return a Promise: %s.', gettype($values))
                    ));
                }

                if (count($values) !== count($keys)) {
                    $this->handleFailedDispatch($batch, new \RuntimeException(
                       DataLoader::class . ' must be constructed with a function which accepts ' .
                       'an array of keys and returns a Promise which resolves to an array of values, but ' .
                       'the function did not return a Promise of an array of the same length as the array of keys.' .
                       sprintf("\n Keys: %s\n Values: %s\n", count($keys), count($values))
                    ));
                }

                // Handle the batch by resolving the promises and rejecting ones that return Exceptions.
                foreach ($batch as $index => $queueItem) {
                    $value = $values[$index];
                    if ($value instanceof \Exception) {
                        $queueItem['reject']($value);
                    } else {
                        $queueItem['resolve']($value);
                    }
                }
            },
            function ($error) use ($batch) {
                $this->handleFailedDispatch($batch, $error);
            }
        );
    }

    /**
     * Dispatches the given queue in multiple batches.
     *
     * @param $queue
     * @param int $maxBatchSize
     *
     * @return void
     */
    private function dispatchQueueInMultipleBatches($queue, $maxBatchSize)
    {
        $numberOfBatchesToDispatch = count($queue) / $maxBatchSize;

        for ($i = 0; $i < $numberOfBatchesToDispatch; $i++) {
            $this->dispatchQueueBatch(
                array_slice($queue, $i * $maxBatchSize, $maxBatchSize)
            );
        }
    }

    /**
     * Handles the failed batch dispatch.
     *
     * @param $batch
     * @param \Exception $error
     *
     * @return void
     */
    private function handleFailedDispatch($batch, \Exception $error)
    {
        foreach ($batch as $index => $queueItem) {
            // We don't want to cache individual loads if the entire batch dispatch fails.
            $this->clear($queueItem['key']);
            $queueItem['reject']($error);
        }
    }
}
