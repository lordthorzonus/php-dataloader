<?php


namespace leinonen\DataLoader;


use React\EventLoop\LoopInterface;
use React\Promise\Promise;

class DataLoader
{
    /**
     * @var callable
     */
    private $batchLoadFunction;

    /**
     * @var array
     */
    private $options;

    /**
     * @var array
     */
    private $promiseQueue = [];

    /**
     * @var array
     */
    private $promiseCache = [];

    /**
     * Initiates a new DataLoader.
     *
     * @param callable $batchLoadFunction
     * @param LoopInterface $loop
     * @param array $options
     */
    public function __construct(callable $batchLoadFunction, LoopInterface $loop, $options = [])
    {
        $this->batchLoadFunction = $batchLoadFunction;
        $this->options = $options;
        $this->eventLoop = $loop;
    }

    /**
     * Returns a Promise for the value represented by the given key.
     *
     * @param int|string $key
     *
     * @return Promise
     */
    public function load($key)
    {
        $cacheKey = $key;

        if(isset($this->promiseCache[$cacheKey])) {
            return $this->promiseCache[$cacheKey];
        }

        $promise = new Promise(function (callable $resolve, callable $reject) use ($key) {

            $this->promiseQueue[] = [
                'key' => $key,
                'resolve' => $resolve,
                'reject' => $reject,
            ];

            if(count($this->promiseQueue) === 1) {
                $this->eventLoop->nextTick(function() {
                    $this->dispatchQueue();
                });
            }
        });

        $this->promiseCache[$cacheKey] = $promise;

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
        return \React\Promise\all(array_map(function ($key) {
            return $this->load($key);
        }, $keys));
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
        $cacheKey = $key;

        if(isset($this->promiseCache[$cacheKey])) {
            unset($this->promiseCache[$cacheKey]);
        }

        return $this;
    }

    /**
     * Clears the entire cache. Returns itself for method chaining.
     *
     * @return $this
     */
    public function clearAll()
    {
        $this->promiseCache = [];

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
        $cacheKey = $key;

        if(!isset($this->promiseCache[$cacheKey])) {
            $this->promiseCache[$cacheKey] = $value;
        }

        return $this;
    }

    /**
     * Resets and dispatches the DataLoaders queue.
     */
    private function dispatchQueue()
    {
        $queue = $this->promiseQueue;
        $this->promiseQueue = [];

        $maxBatchSize = isset($this->options['maxBatchSize']) ? $this->options['maxBatchSize'] : null;

        if($maxBatchSize && $maxBatchSize > 0 && $maxBatchSize < count($queue)) {
            $this->dispatchQueueInMultipleBatches($queue, $maxBatchSize);
        } else {
            $this->dispatchQueueBatch($queue);
        }
    }

    /**
     * Dispatches a batch of a queue. The given batch can also be the whole queue.
     *
     * @param $batch
     */
    private function dispatchQueueBatch($batch)
    {
        $keys = array_map(function ($queueItem) {
            return $queueItem['key'];
        }, $batch);

        /** @var Promise $batchPromise */
        $batchPromise = call_user_func($this->batchLoadFunction, $keys);

        $batchPromise->then(function ($values) use ($keys, $batch) {

            $index = 0;

            foreach ($batch as $queueItem) {
                $value = $values[$index];
                $queueItem['resolve']($value);

                $index++;
            }

        });

    }

    /**
     * Dispatches the given queue in multiple batches.
     *
     * @param $queue
     * @param int $maxBatchSize
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
}
