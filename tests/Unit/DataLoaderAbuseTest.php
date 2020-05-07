<?php

namespace leinonen\DataLoader\Tests\Unit;

use React\Promise\Promise;
use React\EventLoop\Factory;
use PHPUnit\Framework\TestCase;
use leinonen\DataLoader\CacheMap;
use React\EventLoop\LoopInterface;
use function React\Promise\resolve;
use leinonen\DataLoader\DataLoader;
use leinonen\DataLoader\DataLoaderException;

class DataLoaderAbuseTest extends TestCase
{
    /**
     * @var LoopInterface
     */
    private $eventLoop;

    public function setUp(): void
    {
        $this->eventLoop = Factory::create();
    }

    /**
     * @test
     */
    public function load_method_requires_an_actual_key()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('leinonen\DataLoader\DataLoader::load must be called with a value, but got null');

        $loader = $this->createDataLoader(function ($keys) {
            return resolve($keys);
        });
        $loader->load(null);
    }

    /** @test */
    public function falsey_values_are_however_permitted()
    {
        $loader = $this->createDataLoader(function ($keys) {
            return resolve($keys);
        });
        $this->assertInstanceOf(Promise::class, $loader->load(0));
    }

    /**
     * @test
     */
    public function load_many_requires_actual_keys()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('leinonen\DataLoader\DataLoader::load must be called with a value, but got null');

        $loader = $this->createDataLoader(function ($keys) {
            return resolve($keys);
        });
        $loader->loadMany([null, null]);
    }

    /**
     * @test
     */
    public function batch_function_must_return_a_promise_not_null()
    {
        $badLoader = $this->createDataLoader(function ($keys) {
        });

        $exception = null;

        $badLoader->load(1)->then(null, function ($value) use (&$exception) {
            $exception = $value;
        });

        $this->eventLoop->run();

        /** @var DataLoaderException $exception */
        $expectedExceptionMessage = 'leinonen\DataLoader\DataLoader must be constructed with a function which accepts an array of keys '
            . 'and returns a Promise which resolves to an array of values the function returned NULL.';
        $this->assertInstanceOf(DataLoaderException::class, $exception);
        $this->assertSame($expectedExceptionMessage, $exception->getMessage());
    }

    /**
     * @test
     */
    public function batch_function_must_return_a_promise_not_a_array()
    {
        $badLoader = $this->createDataLoader(function ($keys) {
            return $keys;
        });

        $exception = null;

        $badLoader->load(1)->then(null, function ($value) use (&$exception) {
            $exception = $value;
        });

        $this->eventLoop->run();

        /** @var DataLoaderException $exception */
        $expectedExceptionMessage = 'leinonen\DataLoader\DataLoader must be constructed with a function which accepts an array of keys '
            . 'and returns a Promise which resolves to an array of values the function returned array.';
        $this->assertInstanceOf(DataLoaderException::class, $exception);
        $this->assertSame($expectedExceptionMessage, $exception->getMessage());
    }

    /**
     * @test
     */
    public function batch_function_must_return_a_promise_not_a_value()
    {
        $badLoader = $this->createDataLoader(function ($keys) {
            return 1;
        });

        $exception = null;

        $badLoader->load(1)->then(null, function ($value) use (&$exception) {
            $exception = $value;
        });

        $this->eventLoop->run();

        /** @var DataLoaderException $exception */
        $expectedExceptionMessage = 'leinonen\DataLoader\DataLoader must be constructed with a function which accepts an array of keys '
            . 'and returns a Promise which resolves to an array of values the function returned integer.';
        $this->assertInstanceOf(DataLoaderException::class, $exception);
        $this->assertSame($expectedExceptionMessage, $exception->getMessage());
    }

    /**
     * @test
     */
    public function batch_function_must_return_a_promise_not_an_object()
    {
        $badLoader = new DataLoader(
            function ($keys) {
                return new \stdClass();
            }, $this->eventLoop, new CacheMap()
        );
        $exception = null;

        $badLoader->load(1)->then(null, function ($value) use (&$exception) {
            $exception = $value;
        });

        $this->eventLoop->run();

        /** @var DataLoaderException $exception */
        $expectedExceptionMessage = 'leinonen\DataLoader\DataLoader must be constructed with a function which accepts an array of keys '
            . 'and returns a Promise which resolves to an array of values the function returned object.';
        $this->assertInstanceOf(DataLoaderException::class, $exception);
        $this->assertSame($expectedExceptionMessage, $exception->getMessage());
    }

    /**
     * @test
     */
    public function batch_function_must_return_a_promise_of_an_array_not_null()
    {
        $badLoader = $this->createDataLoader(function ($keys) {
            return resolve();
        });

        $exception = null;

        $badLoader->load(1)->then(null, function ($value) use (&$exception) {
            $exception = $value;
        });

        $this->eventLoop->run();

        /** @var DataLoaderException $exception */
        $expectedExceptionMessage = 'leinonen\DataLoader\DataLoader must be constructed with a function which accepts an array of keys '
            . 'and returns a Promise which resolves to an array of values not return a Promise: NULL.';
        $this->assertInstanceOf(DataLoaderException::class, $exception);
        $this->assertSame($expectedExceptionMessage, $exception->getMessage());
    }

    /**
     * @test
     */
    public function batch_function_must_promise_an_array_of_correct_length()
    {
        $emptyArrayLoader = $this->createDataLoader(function ($keys) {
            return resolve([]);
        });

        $exception = null;

        $emptyArrayLoader->load(1)->then(null, function ($value) use (&$exception) {
            $exception = $value;
        });

        $this->eventLoop->run();

        /** @var DataLoaderException $exception */
        $expectedExceptionMessage = 'leinonen\DataLoader\DataLoader must be constructed with a function which accepts an array of keys '
            . 'and returns a Promise which resolves to an array of values, '
            . 'but the function did not return a Promise of an array of the same length as the array of keys.'
            . "\n Keys: 1\n Values: 0\n";
        $this->assertInstanceOf(DataLoaderException::class, $exception);
        $this->assertSame($expectedExceptionMessage, $exception->getMessage());
    }

    /**
     * Creates a simple DataLoader.
     *
     * @param $batchLoadFunction
     * @param array $options
     * @return DataLoader
     */
    private function createDataLoader($batchLoadFunction, $options = null)
    {
        return new DataLoader($batchLoadFunction, $this->eventLoop, new CacheMap(), $options);
    }
}
