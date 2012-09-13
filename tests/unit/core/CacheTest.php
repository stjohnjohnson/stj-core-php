<?php

namespace STJ\Core;

use UnitTest;

/**
 * Cache Test
 *
 * @see https://github.com/stjohnjohnson/stj-core-php
 */
class CacheTest extends UnitTest {
  /**
   * @test
   * @group Cache
   * @group Cache.store
   * @covers stj\Cache
   * @dataProvider cacheProvider
   */
  public function store($key, $value) {
    Cache::set($key, $value);
    $this->assertSame($value, apc_fetch($key));
  }

  /**
   * @test
   * @group Cache
   * @group Cache.retrieve
   * @covers stj\Cache
   * @dataProvider cacheProvider
   */
  public function retrieve($key, $value) {
    $this->store($key, $value);
    $this->assertSame($value, apc_fetch($key));
    $this->assertSame($value, Cache::get($key));
  }

  /**
   * @test
   * @group Cache
   * @group Cache.delete
   * @covers stj\Cache
   * @dataProvider cacheProvider
   */
  public function delete($key, $value) {
    $this->retrieve($key, $value);

    Cache::delete($key);
    $this->assertFalse(apc_fetch($key));
    $this->assertFalse(Cache::get($key));
  }

  /**
   * Data Provider for Cache Tests
   *
   * @return array
   */
  public function cacheProvider() {
    $cases = array();

    foreach (range(0, 10) as $i) {
      $cases[] = array(__METHOD__ . $i, mt_rand(110, 99999));
    }

    return $cases;
  }
}
