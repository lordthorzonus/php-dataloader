<?php


namespace leinonen\DataLoader;


class DataLoaderOptions
{
    /**
     * @var bool
     */
    private $shouldBatch;
    /**
     * @var null
     */
    private $maxBatchSize;
    /**
     * @var bool
     */
    private $shouldCache;

    /**
     * Initiates new DataLoaderOptions.
     *
     * @param bool $shouldBatch
     * @param null|int $maxBatchSize
     * @param bool $shouldCache
     */
    public function __construct(
        $shouldBatch = true,
        $maxBatchSize = null,
        $shouldCache = true
    ) {
        $this->validateOptions($shouldBatch, $maxBatchSize, $shouldCache);
        $this->shouldBatch = $shouldBatch;
        $this->maxBatchSize = $maxBatchSize;
        $this->shouldCache = $shouldCache;
    }

    /**
     * @return boolean
     */
    public function shouldBatch()
    {
        return $this->shouldBatch;
    }

    /**
     * @return null|int
     */
    public function getMaxBatchSize()
    {
        return $this->maxBatchSize;
    }

    /**
     * @return boolean
     */
    public function shouldCache()
    {
        return $this->shouldCache;
    }

    /**
     * Validates the options.
     *
     * @param bool $shouldBatch
     * @param null|int $maxBatchSize
     * @param bool $shouldCache
     */
    private function validateOptions($shouldBatch, $maxBatchSize, $shouldCache)
    {
        if (!is_bool($shouldBatch)) {
            throw new \InvalidArgumentException('Expected argument $shouldBatch to be a boolean');
        }

        if (!is_bool($shouldCache)) {
            throw new \InvalidArgumentException('Expected argument $shouldCache to be a boolean');
        }

        if ($maxBatchSize !== null && !is_int($maxBatchSize)) {
            throw new \InvalidArgumentException('Expected argument $maxBatchSize to be null or a integer');
        }
    }
}
