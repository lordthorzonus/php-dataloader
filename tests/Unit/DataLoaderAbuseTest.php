<?php


namespace leinonen\DataLoader\Tests\Unit;


use leinonen\DataLoader\DataLoader;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;

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
     * @expectedException \RuntimeException
     * @expectedExceptionMessage leinonen\DataLoader\DataLoader must be constructed with a function which accepts
     * an array of keys and returns a Promise which resolves to an array of values not return a Promise: 1.
     */
    public function batch_function_must_return_a_promise_not_a_value()
    {
        $badLoader = new DataLoader(function ($keys) {
            return $keys;
        }, $this->eventLoop);

        $badLoader->load(1);
        $this->eventLoop->run();
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage leinonen\DataLoader\DataLoader must be constructed with a function which accepts
     * an array of keys and returns a Promise which resolves to an array of values not return a Promise: null.
     */
    public function batch_function_must_return_a_promise_of_an_array_not_null()
    {
        $badLoader = new DataLoader(function ($keys) {
            return null;
        }, $this->eventLoop);

        $badLoader->load(1);
        $this->eventLoop->run();
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
        $identityLoader = new DataLoader(function ($keys) {
            return \React\Promise\resolve($keys);
        }, $this->eventLoop, $options);

        return $identityLoader;
    }
}