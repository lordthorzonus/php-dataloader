<?php


namespace leinonen\DataLoader\Tests\Unit;


use leinonen\DataLoader\DataLoader;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;

class DataLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Property where all the identityLoaders calls are stored during a test.
     *
     * @var array
     */
    private $loadCalls;

    /**
     * @var LoopInterface
     */
    private $eventLoop;

    public function setUp()
    {
        $this->eventLoop = Factory::create();

        parent::setUp();
    }

    /** @test */
    public function it_builds_a_really_simple_data_loader()
    {
        $identityLoader = new DataLoader(function ($keys) {
            return \React\Promise\resolve($keys);
        }, $this->eventLoop);

        /** @var Promise $promise1 */
        $promise1 = $identityLoader->load(1);

        $this->assertInstanceOf(Promise::class, $promise1);

        $value1 = null;

        $promise1->then(function ($value) use (&$value1) {
            $value1 = $value;
        });
        $this->eventLoop->run();

        $this->assertEquals(1, $value1);
    }

    /** @test */
    public function it_supports_loading_multiple_keys_in_one_call()
    {
        $identityLoader = $this->createIdentityLoader();
        $promiseAll = $identityLoader->loadMany([1, 2]);
        $this->assertInstanceOf(Promise::class, $promiseAll);

        $values = null;
        $promiseAll->then(function ($returnValues) use (&$values) {
            $values = $returnValues;
        });

        $this->eventLoop->run();

        $this->assertEquals([1, 2], $values);

        $emptyPromise = $identityLoader->loadMany([]);
        $this->assertInstanceOf(Promise::class, $emptyPromise);

        $empty = null;
        $emptyPromise->then(function ($returnValue) use (&$empty) {
            $empty = $returnValue;
        });

        $this->eventLoop->run();

        $this->assertEquals([], $empty);
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

    /** @test */
    public function it_can_clear_a_single_value_in_loader()
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

        $identityLoader->clear('A');

        $a2 = null;
        $b2 = null;

        \React\Promise\all([
            $identityLoader->load('A'),
            $identityLoader->load('B')
        ])->then(function ($returnedValues) use (&$a2, &$b2) {
            $a2 = $returnedValues[0];
            $b2 = $returnedValues[1];
        });

        $this->eventLoop->run();

        $this->assertEquals('A', $a2);
        $this->assertEquals('B', $b2);

        $this->assertEquals([['A', 'B'], ['A']], $this->loadCalls);
    }

    /** @test */
    public function it_can_clear_all_values_in_loader()
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

        $identityLoader->clearAll();

        $a2 = null;
        $b2 = null;

        \React\Promise\all([
            $identityLoader->load('A'),
            $identityLoader->load('B')
        ])->then(function ($returnedValues) use (&$a2, &$b2) {
            $a2 = $returnedValues[0];
            $b2 = $returnedValues[1];
        });

        $this->eventLoop->run();

        $this->assertEquals('A', $a2);
        $this->assertEquals('B', $b2);

        $this->assertEquals([['A', 'B'], ['A', 'B']], $this->loadCalls);
    }

    /** @test */
    public function it_can_prime_the_cache()
    {
        $identityLoader = $this->createIdentityLoader();

        $identityLoader->prime('A', 'A');

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

        $this->assertEquals([['B']], $this->loadCalls);
    }
    
    /** @test */
    public function it_does_not_prime_keys_that_already_exist()
    {
        $identityLoader = $this->createIdentityLoader();

        $identityLoader->prime('A', 'X');

        $a1 = null;
        $b1 = null;

        \React\Promise\all([
            $identityLoader->load('A'),
            $identityLoader->load('B')
        ])->then(function ($returnedValues) use (&$a1, &$b1) {
            $a1 = $returnedValues[0];
            $b1 = $returnedValues[1];
        });

        $this->eventLoop->run();

        $this->assertEquals('X', $a1);
        $this->assertEquals('B', $b1);

        $identityLoader->prime('A', 'Y');
        $identityLoader->prime('B', 'Y');

        $a2 = null;
        $b2 = null;

        \React\Promise\all([
            $identityLoader->load('A'),
            $identityLoader->load('B')
        ])->then(function ($returnedValues) use (&$a2, &$b2) {
            $a2 = $returnedValues[0];
            $b2 = $returnedValues[1];
        });

        $this->eventLoop->run();

        $this->assertEquals('X', $a2);
        $this->assertEquals('B', $b2);

        $this->assertEquals([['B']], $this->loadCalls);
    }

    /** @test */
    public function it_allows_forcefully_priming_the_cache()
    {
        $identityLoader = $this->createIdentityLoader();

        $identityLoader->prime('A', 'X');

        $a1 = null;
        $b1 = null;

        \React\Promise\all([
            $identityLoader->load('A'),
            $identityLoader->load('B')
        ])->then(function ($returnedValues) use (&$a1, &$b1) {
            $a1 = $returnedValues[0];
            $b1 = $returnedValues[1];
        });

        $this->eventLoop->run();

        $this->assertEquals('X', $a1);
        $this->assertEquals('B', $b1);

        $identityLoader->clear('A')->prime('A', 'Y');
        $identityLoader->clear('B')->prime('B', 'Y');

        $a2 = null;
        $b2 = null;

        \React\Promise\all([
            $identityLoader->load('A'),
            $identityLoader->load('B')
        ])->then(function ($returnedValues) use (&$a2, &$b2) {
            $a2 = $returnedValues[0];
            $b2 = $returnedValues[1];
        });

        $this->eventLoop->run();

        $this->assertEquals('Y', $a2);
        $this->assertEquals('Y', $b2);

        $this->assertEquals([['B']], $this->loadCalls);
    }


    /**
     * Creates a simple DataLoader which returns the given keys as values.
     *
     * @param array $options
     *
     * @return DataLoader
     */
    private function createIdentityLoader($options = [])
    {
        $this->loadCalls = [];

        $identityLoader = new DataLoader(function ($keys) {
            $this->loadCalls[] = $keys;

            return \React\Promise\resolve($keys);
        }, $this->eventLoop,  $options);

        return $identityLoader;
    }

    /**
     * Creates a simple DataLoader which returns the given keys as values.
     *
     * @param array $options
     *
     * @return DataLoader
     */
    private function createEvenLoader($options = [])
    {
        $this->loadCalls = [];

        $evenLoader = new DataLoader(function ($keys) {
            $this->loadCalls[] = $keys;

            return \React\Promise\resolve(array_map(function ($key){
                if ($key % 2 === 0) {
                    return $key;
                }

                throw new \Exception("Odd: {$key}");
            }, $keys));
        }, $this->eventLoop,  $options);

        return $evenLoader;
    }


}
