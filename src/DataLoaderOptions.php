<?php

namespace leinonen\DataLoader;

class DataLoaderOptions
{
    /**
     * @var bool
     */
    private $shouldBatch;

    /**
     * @var null|int
     */
    private $maxBatchSize;

    /**
     * @var bool
     */
    private $shouldCache;

    /**
     * Initiates new DataLoaderOptions.
     *
     * @param null|int $maxBatchSize
     * @param bool $shouldBatch
     * @param bool $shouldCache
     */
    public function __construct(
        $maxBatchSize = null,
        $shouldBatch = true,
        $shouldCache = true
    ) {
        $this->validateOptions($maxBatchSize, $shouldBatch, $shouldCache);
        $this->shouldBatch = $shouldBatch;
        $this->maxBatchSize = $maxBatchSize;
        $this->shouldCache = $shouldCache;
    }

    /**
     * @return bool
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
     * @return bool
     */
    public function shouldCache()
    {
        return $this->shouldCache;
    }

    /**
     * Validates the options.
     *
     * @param null|int $maxBatchSize
     * @param bool $shouldBatch
     * @param bool $shouldCache
     */
    private function validateOptions($maxBatchSize, $shouldBatch, $shouldCache)
    {
        $this->validateMaxBatchSizeOption($maxBatchSize);
        $this->validateBatchOption($shouldBatch);
        $this->validateCacheOption($shouldCache);
    }

    /**
     * @param bool $shouldBatch
     */
    private function validateBatchOption($shouldBatch)
    {
        if (! \is_bool($shouldBatch)) {
            throw new \InvalidArgumentException('Expected argument $shouldBatch to be a boolean');
        }
    }

    /**
     * @param bool $shouldCache
     */
    private function validateCacheOption($shouldCache)
    {
        if (! \is_bool($shouldCache)) {
            throw new \InvalidArgumentException('Expected argument $shouldCache to be a boolean');
        }
    }

    /**
     * @param null|int $maxBatchSize
     */
    private function validateMaxBatchSizeOption($maxBatchSize)
    {
        if ($maxBatchSize !== null && ! \is_int($maxBatchSize)) {
            throw new \InvalidArgumentException('Expected argument $maxBatchSize to be null or an integer');
        }
    }
}
