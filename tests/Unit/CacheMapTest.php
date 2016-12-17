<?php


namespace leinonen\DataLoader\Tests\Unit;


use leinonen\DataLoader\CacheMap;

class CacheMapTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function it_can_cache_entries()
    {
        $cacheMap = new CacheMap();

        $cacheMap->set('a', 'a');
        $this->assertEquals('a', $cacheMap->get('a'));

        $cacheMap->set('b', 'b');
        $this->assertEquals('b', $cacheMap->get('b'));

        $objectToBeCached = new \stdClass();
        $cacheMap->set(1, $objectToBeCached);

        $this->assertEquals($objectToBeCached, $cacheMap->get(1));
    }

    /** @test */
    public function it_can_use_anything_as_cache_key()
    {
        $keyA = [];
        $keyB = new \stdClass();
        $keyC = 'a';
        $keyD = 1;

        $cacheMap = new CacheMap();

        $cacheMap->set($keyA, 'a');
        $cacheMap->set($keyB, 'b');
        $cacheMap->set($keyC, 'c');
        $cacheMap->set($keyD, 'd');

        $this->assertEquals('a', $cacheMap->get($keyA));
        $this->assertEquals('b', $cacheMap->get($keyB));
        $this->assertEquals('c', $cacheMap->get($keyC));
        $this->assertEquals('d', $cacheMap->get($keyD));
    }

    /** @test */
    public function it_can_returns_false_for_entries_that_do_not_exist()
    {
        $cacheMap = new CacheMap();
        $this->assertFalse($cacheMap->get('nonExisting'));
    }

    /** @test */
    public function it_can_delete_a_value_from_cache_with_given_key()
    {
        $cacheMap = new CacheMap();

        $objectToBeCached = new \stdClass();
        $cacheKey = 1;

        $cacheMap->set($cacheKey, $objectToBeCached);
        $this->assertEquals($objectToBeCached, $cacheMap->get(1));

        $cacheMap->delete(1);
        $this->assertFalse($cacheMap->get(1));
    }
    
    /** @test */
    public function it_can_clear_the_whole_cache()
    {
        $keyA = [];
        $keyB = new \stdClass();
        $keyC = 'a';
        $keyD = 1;

        $cacheMap = new CacheMap();

        $cacheMap->set($keyA, 'a');
        $cacheMap->set($keyB, 'b');
        $cacheMap->set($keyC, 'c');
        $cacheMap->set($keyD, 'd');

        $this->assertEquals('a', $cacheMap->get($keyA));
        $this->assertEquals('b', $cacheMap->get($keyB));
        $this->assertEquals('c', $cacheMap->get($keyC));
        $this->assertEquals('d', $cacheMap->get($keyD));

        $cacheMap->clear();

        $this->assertFalse($cacheMap->get($keyA));
        $this->assertFalse($cacheMap->get($keyB));
        $this->assertFalse($cacheMap->get($keyC));
        $this->assertFalse($cacheMap->get($keyD));
    }

    /** @test */
    public function it_implements_the_countable_interface_for_counting_the_cache_entries()
    {
        $cacheMap = new CacheMap();
        $cacheMap->set('a', 'a');
        $cacheMap->set('b', 'b');

        $this->assertCount(2, $cacheMap);
        $this->assertEquals(2, $cacheMap->count());
    }

    /** @test */
    public function set_replaces_the_value_for_existing_cache_entry_like_it_should()
    {
        $cacheMap = new CacheMap();
        $cacheMap->set('a', 'a');

        $this->assertCount(1, $cacheMap);
        $this->assertEquals('a', $cacheMap->get('a'));

        $cacheMap->set('a', 'b');
        $this->assertCount(1, $cacheMap);
        $this->assertEquals('b', $cacheMap->get('a'));
    }
}
