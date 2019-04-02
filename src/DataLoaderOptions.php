<?php declare(strict_types=1);

namespace leinonen\DataLoader;

final class DataLoaderOptions
{
    /**
     * @var bool
     */
    private  $shouldBatch;

    /**
     * @var null|int
     */
    private $maxBatchSize;

    /**
     * @var bool
     */
    private $shouldCache;

    public function __construct(
        ?int $maxBatchSize = null,
        bool $shouldBatch = true,
        bool $shouldCache = true
    ) {
        $this->validateMaxBatchSizeOption($maxBatchSize);
        $this->shouldBatch = $shouldBatch;
        $this->maxBatchSize = $maxBatchSize;
        $this->shouldCache = $shouldCache;
    }

    public function shouldBatch(): bool
    {
        return $this->shouldBatch;
    }

    public function getMaxBatchSize(): ?int
    {
        return $this->maxBatchSize;
    }

    public function shouldCache(): bool
    {
        return $this->shouldCache;
    }

    private function validateMaxBatchSizeOption(?int $maxBatchSize)
    {
        if ($maxBatchSize !== null && $maxBatchSize < 0) {
            throw new \InvalidArgumentException('Expected argument $maxBatchSize to be null or a positive integer');
        }
    }
}
