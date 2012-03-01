<?php

namespace stj;

/**
 * Converts fancy / URLs into an accessible array
 *
 * @see https://github.com/stjohnjohnson/stj-core-php
 */
class Route {
  private static $_values = null;

  /**
   * Loads values from Request_URI
   *
   * @param bool $force
   *   Force a reload
   */
  public static function reload($force = false) {
    if (self::$_values === null || $force) {
      $data = trim($_SERVER['REQUEST_URI'], '/');
      // Find ? and stop there
      if (strpos($data, '?') !== false) {
        $data = rtrim(substr($data, 0, strpos($data, '?')), '/');
      }
      self::$_values = explode('/', $data);
    }
  }

  /**
   * Exports values as array
   *
   * @return array of key => values
   */
  public static function export() {
    self::reload();
    return self::$_values;
  }

  /**
   * Returns the value @ position $column
   *
   * @param int $column
   *   Column number
   * @return varied
   * @return empty string if missing
   */
  public static function get($column) {
    self::reload();
    if (isset(self::$_values[$column])) {
      return self::$_values[$column];
    } else {
      return '';
    }
  }

  /**
   * Sets the value @ position $column
   *
   * @param int $column
   *   Column number
   * @param varied $value
   *   Value to set
   */
  public static function set($column, $value) {
    self::reload();
    self::$_values[$column] = $value;
  }

  /**
   * Attempts to match a given 'route' against the current URI
   *
   * @note /request/view/:id/ against /request/view/15
   *       will return true and give $params['id'] = 15
   *
   * @see http://expressjs.com/guide.html#passing-route%20control
   * @param string $route
   *   Route to match against - starting from '/' - named params start with $
   * @param array $params
   *   List of params to be set from matched route
   * @return bool if found
   */
  public static function match($route, array &$params = array()) {
    self::reload();

    $base = '/' . implode('/', self::$_values) . '/';
    $match = preg_replace('/\\\[:](\w+)/', '(?<\1>[^\/]+)', preg_quote($route, '/'));
    $count = preg_match("/^$match/", $base, $params);

    // Remove extra fields
    foreach ($params as $key => $value) {
      if (ctype_digit((string) $key)) {
        unset($params[$key]);
      }
    }
    return $count === 1;
  }
}