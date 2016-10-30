<?php


namespace leinonen\DataLoader\Tests\Unit;


use leinonen\DataLoader\DataLoader;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;

class DataLoaderTest extends \PHPUnit_Framework_TestCase
{

    private $loadCalls;

    /**
     * @var LoopInterface
     */
    private $eventLoop;

    public function setUp()
    {
        $this->eventLoop = Factory::create();
    }

    /** @test */
    public function it_builds_a_really_simple_data_loader()
    {
        $identityLoader = new DataLoader(function ($keys) {
            return \React\Promise\resolve($keys)->then(function ($value) {
                return $value;
            });
        }, $this->eventLoop);

        /** @var Promise $promise1 */
        $promise1 = $identityLoader->load(1);

        $this->assertInstanceOf(Promise::class, $promise1);

        $value1 = null;

        $promise1->then(function ($value) use (&$value1) {
            $value1 = $value;
        });$this->eventLoop->run();
        $this->assertEquals(1, $value1);
    }

    /** @test */
    public function it_batches_multiple_requests()
    {
        $identityLoader = $this->createIdentityLoader();

        $promise1 = $identityLoader->load(1);
        $promise2 = $identityLoader->load(2);

        $values = [];

        \React\Promise\all([$promise1, $promise2])->then(function ($returnedValues) use (&$values) {
            $values = $returnedValues;
        });

        $this->eventLoop->run();

        $this->assertEquals(1, $values[0]);
        $this->assertEquals(2, $values[1]);

        $this->assertEquals([[1, 2]], $this->loadCalls);
    }

    /** @test */
    public function it_batches_multiple_requests_with_max_batch_sizes()
    {
        $identityLoader = $this->createIdentityLoader(['maxBatchSize' => 2]);

        $promise1 = $identityLoader->load(1);
        $promise2 = $identityLoader->load(2);
        $promise3 = $identityLoader->load(3);

        $values = [];

        \React\Promise\all([$promise1, $promise2, $promise3])->then(function ($returnedValues) use (&$values) {
            $values = $returnedValues;
        });

        $this->eventLoop->run();

        $this->assertEquals(1, $values[0]);
        $this->assertEquals(2, $values[1]);
        $this->assertEquals(3, $values[2]);

        $this->assertEquals([[1, 2], [3]], $this->loadCalls);
    }

    /** @test */
    public function it_coalesces_identical_requests()
    {
        $identityLoader = $this->createIdentityLoader();

        $promise1a = $identityLoader->load(1);
        $promise1b = $identityLoader->load(1);

        $this->assertEquals($promise1a, $promise1b);

        $values = [];

        \React\Promise\all([$promise1a, $promise1b])->then(function ($returnedValues) use (&$values) {
            $values = $returnedValues;
        });

        $this->eventLoop->run();

        $this->assertEquals(1, $values[0]);
        $this->assertEquals(1, $values[1]);

        $this->assertEquals([[1]], $this->loadCalls);
    }

    /** @test */
    public function it_caches_repeated_requests()
    {
        $identityLoader = $this->createIdentityLoader();

        $a = null;
        $b = null;

        \React\Promise\all([
            $identityLoader->load('A'),
            $identityLoader->load('B')
        ])->then(function ($returnedValues) use (&$a, &$b) {
            $a = $returnedValues[0];
            $b = $returnedValues[1];
        });

        $this->eventLoop->run();

        $this->assertEquals('A', $a);
        $this->assertEquals('B', $b);

        $this->assertEquals([['A', 'B']], $this->loadCalls);

        $a2 = null;
        $c = null;

        \React\Promise\all([
            $identityLoader->load('A'),
            $identityLoader->load('C')
        ])->then(function ($returnedValues) use (&$a2, &$c) {
            $a2 = $returnedValues[0];
            $c = $returnedValues[1];
        });

        $this->eventLoop->run();

        $this->assertEquals('A', $a2);
        $this->assertEquals('C', $c);

        $this->assertEquals([['A', 'B'], ['C']], $this->loadCalls);

        $a3 = null;
        $b2 = null;
        $c2 = null;

        \React\Promise\all([
            $identityLoader->load('A'),
            $identityLoader->load('B'),
            $identityLoader->load('C')
        ])->then(function ($returnedValues) use (&$a3, &$b2, &$c2) {
            $a3 = $returnedValues[0];
            $b2 = $returnedValues[1];
            $c2 = $returnedValues[2];
        });

        $this->eventLoop->run();

        $this->assertEquals('A', $a3);
        $this->assertEquals('B', $b2);
        $this->assertEquals('C', $c2);

        $this->assertEquals([['A', 'B'], ['C']], $this->loadCalls);
    }

    private function createIdentityLoader($options = [])
    {
        $this->loadCalls = [];

        $identityLoader = new DataLoader(function ($keys) {
            array_push($this->loadCalls, $keys);

            return \React\Promise\resolve($keys)->then(function ($value) {
                return $value;
            });
        }, $this->eventLoop,  $options);

        return $identityLoader;
    }


}
