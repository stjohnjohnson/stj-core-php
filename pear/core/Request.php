<?php

namespace STJ\Core;

/**
 * Simple singleton based access to $_REQUEST, $_GET, $_POST
 *
 * @see https://github.com/stjohnjohnson/stj-core-php
 */
class Request {
  /**
   * Get variable from $_REQUEST[$key]
   *
   * @param string $key
   *   Unique index of the object
   * @param mixed $default
   *   Default value to return if $_REQUEST[$key] is not present
   * @return mixed
   */
  public static function get($key, $default = null) {
    return self::has($key) ? $_REQUEST[$key] : $default;
  }

  /**
   * Sets a variable into the $_REQUEST array
   *
   * @param string $key
   *   Unique index of the object
   * @param mixed $value
   *   Value to set
   */
  public static function set($key, $value) {
    $_REQUEST[$key] = $value;
  }

  /**
   * Check is variable exists in $_REQUEST[$key]
   *
   * @param string $key
   *   Unique index of the object
   * @return boolean
   */
  public static function has($key) {
    return array_key_exists($key, $_REQUEST);
  }

  /**
   * Request method POST/GET/etc.
   *
   * @return string
   *   POST, GET, PUT, etc.
   */
  public static function method() {
    return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null;
  }

  /**
   * Return paramaters from GET/POST
   *
   * @param array $exclude
   *   List of keys to exclude
   * @return array
   *   Hash of parameters
   */
  public static function params(array $exclude = array()) {
    $params = $_REQUEST;
    foreach ($exclude as $key) {
      unset($params[$key]);
    }

    return $params;
  }

  /**
   * Get current host (with https)
   *
   * @return string
   */
  public static function host() {
    $base = 'http';
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
      $base .= 's';
    }
    return $base . '://' . rtrim($_SERVER['HTTP_HOST'], '/');
  }
}
