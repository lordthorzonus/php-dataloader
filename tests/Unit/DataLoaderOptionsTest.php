<?php

namespace leinonen\DataLoader\Tests\Unit;

use PHPUnit\Framework\TestCase;
use leinonen\DataLoader\DataLoaderOptions;

class DataLoaderOptionsTest extends TestCase
{
    /** @test */
    public function it_can_be_initiated()
    {
        $options = new DataLoaderOptions();
        $this->assertEquals(null, $options->getMaxBatchSize());
        $this->assertEquals(true, $options->shouldBatch());
        $this->assertEquals(true, $options->shouldCache());

        $options = new DataLoaderOptions(2, false, false);
        $this->assertEquals(2, $options->getMaxBatchSize());
        $this->assertEquals(false, $options->shouldBatch());
        $this->assertEquals(false, $options->shouldCache());

        $options = new DataLoaderOptions(null, false, true);
        $this->assertEquals(null, $options->getMaxBatchSize());
        $this->assertEquals(false, $options->shouldBatch());
        $this->assertEquals(true, $options->shouldCache());
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_maxBatchSize_is_not_a_positive_integer()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected argument $maxBatchSize to be null or a positive integer');
        new DataLoaderOptions(-2);
    }
}
