<?php

namespace leinonen\DataLoader\Tests\Unit;

use React\Promise\Promise;
use React\EventLoop\Factory;
use leinonen\DataLoader\CacheMap;
use React\EventLoop\LoopInterface;
use leinonen\DataLoader\DataLoader;

class DataLoaderAbuseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoopInterface
     */
    private $eventLoop;

    public function setUp()
    {
        $this->eventLoop = Factory::create();
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage leinonen\DataLoader\DataLoader::load must be called with a value, but got null
     */
    public function load_method_requires_an_actual_key()
    {
        $loader = $this->createIdentityLoader();
        $loader->load(null);
    }

    /** @test */
    public function falsey_values_are_however_permitted()
    {
        $loader = $this->createIdentityLoader();
        $this->assertInstanceOf(Promise::class, $loader->load(0));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage leinonen\DataLoader\DataLoader::load must be called with a value, but got null
     */
    public function load_many_requires_actual_keys()
    {
        $loader = $this->createIdentityLoader();
        $loader->loadMany([null, null]);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage leinonen\DataLoader\DataLoader must be constructed with a function which accepts
     * an array of keys and returns a Promise which resolves to an array of values not return a Promise: null.
     */
    public function batch_function_must_return_a_promise_not_null()
    {
        $badLoader = new DataLoader(
            function ($keys) {
            }, $this->eventLoop, new CacheMap()
        );

        $badLoader->load(1);
        $this->eventLoop->run();
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage leinonen\DataLoader\DataLoader must be constructed with a function which accepts
     * an array of keys and returns a Promise which resolves to an array of values not return a Promise: 1.
     */
    public function batch_function_must_return_a_promise_not_a_value()
    {
        $badLoader = new DataLoader(
            function ($keys) {
                return $keys;
            }, $this->eventLoop, new CacheMap()
        );

        $badLoader->load(1);
        $this->eventLoop->run();
    }

    /**
     * @test
     */
    public function batch_function_must_return_a_promise_of_an_array_not_null()
    {
        $badLoader = new DataLoader(
            function ($keys) {
                return \React\Promise\resolve();
            }, $this->eventLoop, new CacheMap()
        );

        $exception = null;

        $badLoader->load(1)->then(null, function ($value) use (&$exception) {
            $exception = $value;
        });

        $this->eventLoop->run();

        $expectedExceptionMessage = 'leinonen\DataLoader\DataLoader must be constructed with a function which accepts an array of keys '
            . 'and returns a Promise which resolves to an array of values not return a Promise: NULL.';
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals($expectedExceptionMessage, $exception->getMessage());
    }

    /**
     * @test
     */
    public function batch_function_must_promise_an_array_of_correct_length()
    {
        $emptyArrayLoader = new DataLoader(
            function ($keys) {
                return \React\Promise\resolve([]);
            }, $this->eventLoop, new CacheMap()
        );

        $exception = null;

        $emptyArrayLoader->load(1)->then(null, function ($value) use (&$exception) {
            $exception = $value;
        });

        $this->eventLoop->run();

        $expectedExceptionMessage = 'leinonen\DataLoader\DataLoader must be constructed with a function which accepts an array of keys '
            . 'and returns a Promise which resolves to an array of values, '
            . 'but the function did not return a Promise of an array of the same length as the array of keys.'
            . "\n Keys: 1\n Values: 0\n";

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals($expectedExceptionMessage, $exception->getMessage());
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
                return \React\Promise\resolve($keys);
            }, $this->eventLoop, new CacheMap(), $options
        );

        return $identityLoader;
    }
}
