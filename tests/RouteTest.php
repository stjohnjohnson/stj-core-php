<?php

namespace stj;

use PHPUnit_Framework_TestCase;

require_once 'pear/Route.php';

/**
 * Route Test
 *
 * @see https://github.com/stjohnjohnson/stj-core-php
 */
class RouteTest extends PHPUnit_Framework_TestCase {
  /**
   * @test
   * @group Route
   * @group Route.reload
   * @covers stj\Route
   */
  public function reload() {
    $_SERVER['REQUEST_URI'] = '/resource/class/action/id/?asdsd';

    // Call with force
    Route::reload(true);

    // Export values
    $this->assertEquals(array(
        'resource','class','action','id'
    ), Route::export());

    // Change routing
    $_SERVER['REQUEST_URI'] = '/something/else/is/here';

    // Call without force
    Route::reload();

    // Export values
    $this->assertEquals(array(
        'resource','class','action','id'
    ), Route::export());
  }

  /**
   * @test
   * @group Route
   * @group Route.get
   * @covers stj\Route
   * @dataProvider getProvider
   */
  public function get($uri, $params) {
    $_SERVER['REQUEST_URI'] = $uri;
    // Force refresh
    Route::reload(true);

    foreach ($params as $index => $value) {
      $this->assertEquals($value, Route::get($index), $index);
    }
  }

  /**
   * Data Provider for get
   *
   * @return array
   *   Use Cases
   */
  public function getProvider() {
    $cases = array();

    // Nothing
    $cases[] = array('/', array('','','','','','','',''));
    $cases[] = array('', array('','','','','','','',''));

    // One item
    $cases[] = array('/resource', array('resource'));
    $cases[] = array('/resource', array('resource','','','','','','',''));
    // Two items
    $cases[] = array('/resource/class', array('resource','class'));
    $cases[] = array('/resource/class', array('resource','class','','','','','',''));
    // Three items
    $cases[] = array('/resource/class/action', array('resource','class','action','','','','',''));
    // Four items
    $cases[] = array('/resource/class/action', array('resource','class','action','','','','',''));
    // With Query String
    $cases[] = array('/resource/class/action?foo=bar', array('resource','class','action','','','','',''));
    // With Query String and trailing slash
    $cases[] = array('/resource/class/action/?foo=bar', array('resource','class','action','','','','',''));

    return $cases;
  }

  /**
   * @test
   * @group Route
   * @group Route.set
   * @covers stj\Route
   * @dataProvider setProvider
   */
  public function set($uri, $index, $set, $params) {
    $_SERVER['REQUEST_URI'] = $uri;
    // Force refresh
    Route::reload(true);
    Route::set($index, $set);

    foreach ($params as $index => $value) {
      $this->assertEquals($value, Route::get($index), $index);
    }
  }

  /**
   * Data Provider for set
   *
   * @return array
   *   Use Cases
   */
  public function setProvider() {
    $cases = array();

    // Nothing
    $cases[] = array('/', 2, 'foo', array('','','foo','','','','',''));
    $cases[] = array('', 3, 'foo', array('','','','foo','','','',''));

    // One item
    $cases[] = array('/resource', 0, 'foo', array('foo'));
    $cases[] = array('/resource', 5, 'foo', array('resource','','','','','foo','',''));
    // With Query String
    $cases[] = array('/resource/class/action?foo=bar', 5, 'foo', array('resource','class','action','','','foo','',''));
    // With Query String and trailing slash
    $cases[] = array('/resource/class/action/?foo=bar', 5, 'foo', array('resource','class','action','','','foo','',''));

    return $cases;
  }

  /**
   * @test
   * @group Route
   * @group Route.match
   * @covers stj\Route
   * @dataProvider matchProvider
   */
  public function match($uri, $match, $bool, $array) {
    $_SERVER['REQUEST_URI'] = $uri;
    // Force reload
    Route::reload(true);

    $params = array();
    $this->assertEquals($bool, Route::match($match, $params));
    $this->assertEquals($params, $array);
  }

  /**
   * Data Provider for match
   *
   * @return array
   *   Use Cases
   */
  public function matchProvider() {
    $cases = array();

    // Failures
    $cases[] = array('/preferences/', '/news', false, array());
    $cases[] = array('/preferences/', '/news/:id', false, array());
    $cases[] = array('/news/', '/news/:id', false, array());

    // Success
    $cases[] = array('/users/sample/', '/users/', true, array());
    $cases[] = array('/users/sample/', '/users/:name', true, array('name' => 'sample'));
    $cases[] = array('/users/sample/edit/', '/users/:name/:action', true, array('name' => 'sample', 'action' => 'edit'));

    return $cases;
  }
}
