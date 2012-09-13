<?php

namespace STJ\Core;

/**
 * Request Test
 *
 * @see https://github.com/stjohnjohnson/stj-core-php
 */
class RequestTest extends \UnitTest {
  public function setUp() {
    // Reset Request Global
    $_REQUEST = array();
  }

  /**
   * @test
   * @group Request
   * @group Request.get
   * @covers STJ\Core\Request::get
   */
  public function get() {
    $this->assertEquals(null, Request::get(__METHOD__));
    $this->assertEquals(false, Request::get(__METHOD__, false));

    $_REQUEST[__METHOD__] = true;

    $this->assertEquals(true, Request::get(__METHOD__, false));
  }

  /**
   * @test
   * @group Request
   * @group Request.set
   * @covers STJ\Core\Request::set
   */
  public function set() {
    $this->assertFalse(isset($_REQUEST[__METHOD__]));
    Request::set(__METHOD__, 'abc');
    $this->assertTrue(isset($_REQUEST[__METHOD__]));
    $this->assertEquals('abc', $_REQUEST[__METHOD__]);
  }

  /**
   * @test
   * @group Request
   * @group Request.has
   * @covers STJ\Core\Request::has
   */
  public function has() {
    $this->assertFalse(Request::has(__METHOD__));
    $_REQUEST[__METHOD__] = null;
    $this->assertTrue(Request::has(__METHOD__));
    $_REQUEST[__METHOD__] = 'abc';
    $this->assertTrue(Request::has(__METHOD__));
  }

  /**
   * @test
   * @group Request
   * @group Request.method
   * @covers STJ\Core\Request::method
   */
  public function method() {
    $_SERVER['REQUEST_METHOD'] = null;
    $this->assertEquals(null, Request::method());

    $_SERVER['REQUEST_METHOD'] = 'GET';
    $this->assertEquals('GET', Request::method());
  }

  /**
   * @test
   * @group Request
   * @group Request.params
   * @covers STJ\Core\Request::params
   */
  public function params() {
    $_REQUEST = array(
        'search' => 'sandpaper',
         'wssid' => '12345',
          'sort' => 'ASC'
    );

    // Check with everything
    $this->assertEquals(array(
        'search' => 'sandpaper',
         'wssid' => '12345',
          'sort' => 'ASC'
    ), Request::params());

    // Assert with removing items
    $this->assertEquals(array(
        'search' => 'sandpaper'
    ), Request::params(array('wssid','sort')));
  }

  /**
   * @test
   * @group Request
   * @group Request.host
   * @covers STJ\Core\Request::host
   */
  public function host() {
    $_SERVER['HTTP_HOST'] = 'hub.scorchedgalaxy.com';
    $_SERVER['HTTPS'] = 'on';
    $this->assertEquals('https://hub.scorchedgalaxy.com', Request::host());

    // Turn off HTTPS
    unset($_SERVER['HTTPS']);

    $this->assertEquals('http://hub.scorchedgalaxy.com', Request::host());
  }
}
