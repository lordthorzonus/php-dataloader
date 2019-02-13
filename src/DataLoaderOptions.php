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
        ?int $maxBatchSize = null,
        bool $shouldBatch = true,
        bool $shouldCache = true
    ) {
        $this->validateOptions($maxBatchSize, $shouldBatch, $shouldCache);
        $this->shouldBatch = $shouldBatch;
        $this->maxBatchSize = $maxBatchSize;
        $this->shouldCache = $shouldCache;
    }

    /**
     * @return bool
     */
    public function shouldBatch(): bool
    {
        return $this->shouldBatch;
    }

    /**
     * @return null|int
     */
    public function getMaxBatchSize(): ?int
    {
        return $this->maxBatchSize;
    }

    /**
     * @return bool
     */
    public function shouldCache(): bool
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
    private function validateOptions($maxBatchSize, $shouldBatch, $shouldCache): void
    {
        $this->validateMaxBatchSizeOption($maxBatchSize);
    }

    /**
     * @param null|int $maxBatchSize
     */
    private function validateMaxBatchSizeOption($maxBatchSize)
    {
        if (($maxBatchSize !== null && ! \is_int($maxBatchSize)) || (int) $maxBatchSize < 0) {
            throw new \InvalidArgumentException('Expected argument $maxBatchSize to be null or a positive integer');
        }
    }
}
