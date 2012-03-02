<?php

namespace stj;

use PHPUnit_Framework_TestCase;

require_once 'pear/Cache.php';

/**
 * Cache Test
 *
 * @see https://github.com/stjohnjohnson/stj-core-php
 */
class CacheTest extends PHPUnit_Framework_TestCase {
  /**
   * @test
   * @group Cache
   * @group Cache.storeAndFetch
   * @covers stj\Cache
   */
  public function storeAndFetch() {
    $key = __METHOD__;
    $value = 'phpunit test value';
    $this->assertFalse(apc_fetch($key));
    $this->assertFalse(Cache::get($key));

    Cache::set($key, $value);
    $this->assertSame($value, apc_fetch($key));
    $this->assertSame($value, Cache::get($key));
  }

  /**
   * @test
   * @group Cache
   * @group Cache.delete
   * @covers stj\Cache
   */
  public function delete() {
    $key = __METHOD__;
    $value = 'phpunit test value';

    Cache::set($key, $value);
    $this->assertSame($value, apc_fetch($key));
    $this->assertSame($value, Cache::get($key));

    Cache::delete($key);
    $this->assertFalse(apc_fetch($key));
    $this->assertFalse(Cache::get($key));
  }
}
