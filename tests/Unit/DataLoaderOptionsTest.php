<?php


namespace leinonen\DataLoader\Tests\Unit;


use leinonen\DataLoader\DataLoaderOptions;

class DataLoaderOptionsTest extends \PHPUnit_Framework_TestCase
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
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected argument $shouldBatch to be a boolean
     */
    public function it_should_throw_an_exception_if_shouldBatch_is_not_a_boolean()
    {
        new DataLoaderOptions(null, 1);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected argument $shouldCache to be a boolean
     */
    public function it_should_throw_an_exception_if_shouldCache_is_not_a_boolean()
    {
        new DataLoaderOptions(2, true, 2);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected argument $maxBatchSize to be null or a integer
     */
    public function it_should_throw_an_exception_if_maxBatchSize_is_not_null_or_an_integer()
    {
        new DataLoaderOptions(true, true, true);
    }
}
