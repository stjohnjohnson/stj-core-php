<?php

namespace STJ\Core;

/**
 * Session Test
 *
 * @see https://github.com/stjohnjohnson/stj-core-php
 */
class SessionTest extends \UnitTest {
  public function setUp() {
    ini_set('session.use_cookies', '0');
    @session_cache_limiter('');
    @session_destroy();
    SessionMock::reset();
  }

  /**
   * @test
   * @group Session
   * @group Session.store
   * @covers STJ\Core\Session::set
   * @dataProvider sessionProvider
   */
  public function store($key, $value) {
    SessionMock::set($key, $value);
    $this->assertSame($value, $_SESSION[$key]);
  }

  /**
   * @test
   * @group Session
   * @group Session.has
   * @covers STJ\Core\Session::has
   * @dataProvider sessionProvider
   */
  public function has($key, $value) {
    $this->assertFalse(SessionMock::has($key));
    $this->store($key, $value);
    $this->assertTrue(SessionMock::has($key));
  }

  /**
   * @test
   * @group Session
   * @group Session.retrieve
   * @covers STJ\Core\Session::get
   * @dataProvider sessionProvider
   */
  public function retrieve($key, $value) {
    $this->store($key, $value);
    $this->assertSame($value, $_SESSION[$key]);
    $this->assertSame($value, SessionMock::get($key));
  }

  /**
   * @test
   * @group Session
   * @group Session.delete
   * @covers STJ\Core\Session::delete
   * @dataProvider sessionProvider
   */
  public function delete($key, $value) {
    $this->retrieve($key, $value);

    SessionMock::delete($key);
    $this->assertFalse(isset($_SESSION[$key]));
    $this->assertFalse(SessionMock::get($key));
  }

  /**
   * @test
   * @group Session
   * @group Session.init
   * @covers STJ\Core\Session::init
   */
  public function init() {
    SessionMock::reset();

    $this->assertFalse(SessionMock::expose());
    SessionMock::init();
    $this->assertTrue(SessionMock::expose());
  }

  /**
   * Data Provider for Session Tests
   *
   * @return array
   */
  public function sessionProvider() {
    $cases = array();

    foreach (range(0, 10) as $i) {
      $cases[] = array(__METHOD__ . $i, mt_rand(110, 99999));
    }

    return $cases;
  }
}

class SessionMock extends Session {
  public static function reset() {
    self::$_init = false;
  }
  public static function expose() {
    return self::$_init;
  }
}