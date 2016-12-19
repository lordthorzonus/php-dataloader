<?php


namespace leinonen\DataLoader\Tests\Unit;


use leinonen\DataLoader\CacheMap;
use leinonen\DataLoader\DataLoader;
use leinonen\DataLoader\DataLoaderOptions;
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
        $this->loadCalls = [];
        parent::setUp();
    }

    /** @test */
    public function it_builds_a_really_simple_data_loader()
    {
        $identityLoader = new DataLoader(
            function ($keys) {
                return \React\Promise\resolve($keys);
            }, $this->eventLoop, new CacheMap()
        );

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
        $options = new DataLoaderOptions(2);
        $identityLoader = $this->createIdentityLoader($options);

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

    /** @test */
    public function it_resolves_error_to_indicate_failure()
    {
        $evenLoader = $this->createEvenLoader();

        $promise1 = $evenLoader->load(1);
        $exception = null;
        $promise1->then(null, function ($error) use(&$exception) {
            $exception = $error;
        });

        $this->eventLoop->run();

        /** @var \Exception $exception */
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('Odd: 1', $exception->getMessage());
        $this->assertEquals($this->loadCalls, [ [1] ]);

        $promise2 = $evenLoader->load(2);
        $result = null;
        $promise2->then(function ($number) use (&$result) {
            $result = $number;
        });

        $this->eventLoop->run();

        $this->assertEquals(2, $result);

        $this->assertEquals( [ [1], [2] ], $this->loadCalls);
    }

    /** @test */
    public function it_can_represent_failures_and_successes_simultaneously()
    {
        $evenLoader = $this->createEvenLoader();

        $promise1 = $evenLoader->load(1);
        $exception = null;
        $promise1->then(null, function ($error) use (&$exception) {
            $exception = $error;
        });

        $promise2 = $evenLoader->load(2);
        $result = null;
        $promise2->then(function ($number) use (&$result) {
            $result = $number;
        });

        $this->eventLoop->run();

        /** @var \Exception $exception */
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('Odd: 1', $exception->getMessage());

        $this->assertEquals(2, $result);

        $this->assertEquals( [ [1, 2] ], $this->loadCalls);
    }

    /** @test */
    public function it_caches_failed_fetches()
    {
        $exceptionLoader = $this->createExceptionLoader();

        $exceptionA = null;
        $promise = $exceptionLoader->load(1);
        $promise->then(null,function ($error) use (&$exceptionA) {
            $exceptionA = $error;
        });

        $this->eventLoop->run();

        /** @var \Exception $exceptionA */
        $this->assertInstanceOf(\Exception::class, $exceptionA);
        $this->assertEquals('Error: 1', $exceptionA->getMessage());

        $exceptionB = null;
        $promise = $exceptionLoader->load(1);
        $promise->then(null, function ($error) use (&$exceptionB) {
            $exceptionB = $error;
        });

        $this->eventLoop->run();

        /** @var \Exception $exceptionB */
        $this->assertInstanceOf(\Exception::class, $exceptionB);
        $this->assertEquals('Error: 1', $exceptionB->getMessage());

        $this->assertEquals([[1]], $this->loadCalls);
    }

    /** @test */
    public function it_handles_priming_the_cache_with_an_exception()
    {
        $identityLoader = $this->createIdentityLoader();
        $identityLoader->prime(1, new \Exception('Error: 1'));

        $exceptionA = null;
        $promise = $identityLoader->load(1);
        $promise->then(null, function ($error) use (&$exceptionA) {
            $exceptionA = $error;
        });

        $this->eventLoop->run();

        /** @var \Exception $exceptionA */
        $this->assertInstanceOf(\Exception::class, $exceptionA);
        $this->assertEquals('Error: 1', $exceptionA->getMessage());

        $this->assertEquals([], $this->loadCalls);
    }

    /** @test */
    public function it_can_clear_values_from_cache_after_errors()
    {
        $exceptionLoader = $this->createExceptionLoader();

        $exceptionA = null;
        $promise = $exceptionLoader->load(1);
        $promise->then(null, function ($error) use (&$exceptionA, $exceptionLoader) {
            $exceptionLoader->clear(1);
            $exceptionA = $error;
        });

        $this->eventLoop->run();

        /** @var \Exception $exceptionA */
        $this->assertInstanceOf(\Exception::class, $exceptionA);
        $this->assertEquals('Error: 1', $exceptionA->getMessage());

        $exceptionB = null;
        $promise = $exceptionLoader->load(1);
        $promise->then(null, function ($error) use (&$exceptionB, $exceptionLoader) {
            $exceptionLoader->clear(1);
            $exceptionB = $error;
        });

        $this->eventLoop->run();

        /** @var \Exception $exceptionB */
        $this->assertInstanceOf(\Exception::class, $exceptionB);
        $this->assertEquals('Error: 1', $exceptionB->getMessage());

        $this->assertEquals([1], [1], $this->loadCalls);
    }

    /** @test */
    public function it_propagates_error_to_all_loads()
    {
        $failLoader = new DataLoader(
            function ($keys) {
                $this->loadCalls[] = $keys;

                return \React\Promise\reject(new \Exception('I am a terrible loader'));
            }, $this->eventLoop, new CacheMap()
        );

        $promise1 = $failLoader->load(1);
        $promise2 = $failLoader->load(2);

        $exception1 = null;
        $promise1->then(null, function ($error) use (&$exception1) {
            $exception1 = $error;
        });

        $exception2 = null;
        $promise2->then(null, function ($error) use (&$exception2) {
            $exception2 = $error;
        });

        $this->eventLoop->run();

        /** @var \Exception $exception1 */
        $this->assertInstanceOf(\Exception::class, $exception1);
        $this->assertEquals('I am a terrible loader', $exception1->getMessage());

        /** @var \Exception $exception2 */
        $this->assertInstanceOf(\Exception::class, $exception2);
        $this->assertEquals('I am a terrible loader', $exception2->getMessage());

        $this->assertEquals([[1, 2]], $this->loadCalls);
    }

    /** @test */
    public function it_accepts_objects_as_keys()
    {
        $identityLoader = $this->createIdentityLoader();
        $keyA = new \stdClass();
        $keyB = new \stdClass();
        $valueA = null;
        $valueB = null;

        \React\Promise\all([
            $identityLoader->load($keyA),
            $identityLoader->load($keyB)
        ])->then(function ($returnedValues) use (&$valueA, &$valueB) {
            $valueA = $returnedValues[0];
            $valueB = $returnedValues[1];
        });

        $this->eventLoop->run();

        $this->assertEquals($keyA, $valueA);
        $this->assertEquals($keyB, $valueB);
        $this->assertCount(1, $this->loadCalls);
        $this->assertCount(2, $this->loadCalls[0]);
        $this->assertEquals($keyA, $this->loadCalls[0][0]);
        $this->assertEquals($keyB, $this->loadCalls[0][1]);

        // Caching
        $identityLoader->clear($keyA);

        $valueA = null;
        $valueB = null;

        \React\Promise\all([
            $identityLoader->load($keyA),
            $identityLoader->load($keyB)
        ])->then(function ($returnedValues) use (&$valueA, &$valueB) {
            $valueA = $returnedValues[0];
            $valueB = $returnedValues[1];
        });

        $this->eventLoop->run();

        $this->assertCount(2, $this->loadCalls);
        $this->assertCount(1, $this->loadCalls[1]);
        $this->assertEquals($keyA, $this->loadCalls[1][0]);
    }

    /** @test */
    public function it_may_disable_batching()
    {
        $options = new DataLoaderOptions(null, false);
        $identityLoader = $this->createIdentityLoader($options);

        $a = null;
        $b = null;

        \React\Promise\all([
            $identityLoader->load(1),
            $identityLoader->load(2)
        ])->then(function ($returnedValues) use (&$a, &$b) {
            $a = $returnedValues[0];
            $b = $returnedValues[1];
        });

        $this->assertEquals(1, $a);
        $this->assertEquals(2, $b);

        $this->assertEquals([[1], [2]], $this->loadCalls);
    }

    /** @test */
    public function it_may_disable_caching()
    {
        $options = new DataLoaderOptions(null, true, false);
        $identityLoader = $this->createIdentityLoader($options);

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

        $this->assertEquals([['A', 'B'], ['A', 'C']], $this->loadCalls);

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

        $this->assertEquals([['A', 'B'], ['A', 'C'], ['A', 'B', 'C']], $this->loadCalls);
    }

    /** @test */
    public function it_batches_loads_occurring_within_promises()
    {
        $identityLoader = $this->createIdentityLoader();

        \React\Promise\all([
            $identityLoader->load('A'),
            \React\Promise\resolve()->then(function () use ($identityLoader) {
                \React\Promise\resolve()->then(function () use ($identityLoader) {
                    $identityLoader->load('B');
                    \React\Promise\resolve()->then(function () use ($identityLoader) {
                       $identityLoader->load('C');
                       \React\Promise\resolve()->then(function () use ($identityLoader) {
                           $identityLoader->load('D');
                       });
                    });
                });
            })
        ]);

        $this->eventLoop->run();

        $this->assertEquals([['A', 'B', 'C', 'D']], $this->loadCalls);
    }

    /** @test */
    public function it_can_call_a_loader_from_a_loader()
    {
        $deepLoadCalls = [];
        $deepLoader = new DataLoader(
            function ($keys) use (&$deepLoadCalls) {
                $deepLoadCalls[] = $keys;

                return \React\Promise\resolve($keys);
            }, $this->eventLoop, new CacheMap()
        );

        $aLoadCalls = [];
        $aLoader = new DataLoader(
            function ($keys) use (&$aLoadCalls, $deepLoader) {
                $aLoadCalls[] = $keys;

                return $deepLoader->load($keys);
            }, $this->eventLoop, new CacheMap()
        );

        $bLoadCalls = [];
        $bLoader = new DataLoader(
            function ($keys) use (&$bLoadCalls, $deepLoader) {
                $bLoadCalls[] = $keys;

                return $deepLoader->load($keys);
            }, $this->eventLoop, new CacheMap()
        );

        $a1 = null;
        $a2 = null;
        $b1 = null;
        $b2 = null;

        \React\Promise\all([
            $aLoader->load('A1'),
            $bLoader->load('B1'),
            $aLoader->load('A2'),
            $bLoader->load('B2'),
        ])->then(function ($returnedValues) use (&$a1, &$a2, &$b1, &$b2) {
            $a1 = $returnedValues[0];
            $b1 = $returnedValues[1];
            $a2 = $returnedValues[2];
            $b2 = $returnedValues[3];
        });

        $this->eventLoop->run();

        $this->assertEquals('A1', $a1);
        $this->assertEquals('A2', $a2);
        $this->assertEquals('B1', $b1);
        $this->assertEquals('B2', $b2);

        $this->assertEquals([['A1', 'A2']], $aLoadCalls);
        $this->assertEquals([['B1', 'B2']], $bLoadCalls);
        $this->assertEquals([[['A1', 'A2'], ['B1', 'B2']]], $deepLoadCalls);
    }

    /**
     * Creates a simple DataLoader which returns the given keys as values.
     *
     * @param array $options
     *
     * @return DataLoader
     */
    private function createIdentityLoader($options = null)
    {
        $identityLoader = new DataLoader(
            function ($keys) {
                $this->loadCalls[] = $keys;

                return \React\Promise\resolve($keys);
            }, $this->eventLoop, new CacheMap(), $options
        );

        return $identityLoader;
    }

    /**
     * Creates a simple DataLoader which returns the given keys as values.
     *
     * @param array $options
     *
     * @return DataLoader
     */
    private function createEvenLoader($options = null)
    {
        $evenLoader = new DataLoader(
            function ($keys) {
                $this->loadCalls[] = $keys;

                return \React\Promise\resolve(
                    array_map(
                        function ($key) {
                            if ($key % 2 === 0) {
                                return $key;
                            }

                            return new \Exception("Odd: {$key}");
                        },
                        $keys
                    )
                );
            }, $this->eventLoop, new CacheMap(), $options
        );

        return $evenLoader;
    }

    /**
     * Creates a DataLoader which transforms the given keys into Exceptions.
     *
     * @return DataLoader
     */
    private function createExceptionLoader()
    {
        $exceptionLoader = new DataLoader(
            function ($keys) {
                $this->loadCalls[] = $keys;

                return \React\Promise\resolve(
                    array_map(
                        function ($key) {
                            return new \Exception("Error: {$key}");
                        },
                        $keys
                    )
                );

            }, $this->eventLoop, new CacheMap()
        );

        return $exceptionLoader;
    }
}
