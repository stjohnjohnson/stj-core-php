<?php

namespace STJ\Core;

use Exception;

/**
 * Provide static access to a single ini file (with sections)
 *
 * @see https://github.com/stjohnjohnson/stj-core-php
 */
class Conf {
  protected static $_contents = array();

  /**
   * Initialize the INI value
   *
   * @param string $filename
   *   Filename of the INI file
   */
  public static function init($filename) {
    self::$_contents = parse_ini_file($filename, true);
  }

  /**
   * Get a value from [section] index= in the INI file
   *
   * @param string $section
   *   Section of INI file
   * @param string $index
   *   Index of Property
   * @param varied $default
   *   Default value if Section/Index not found
   * @return string
   *   Value of INI file or Default if Not Found
   * @throws ConfException
   *   Thrown on setting not found with no $default provided
   */
  public static function get($section, $index, $default = null) {
    // Index/Section Not Found
    if (!isset(self::$_contents[$section]) ||
        !isset(self::$_contents[$section][$index])) {
      // No default provided
      if (!isset($default)) {
        throw new ConfException("No Settings found for '$section.$index'");
      }

      // Return default
      return $default;
    }

    // Return value
    return self::$_contents[$section][$index];
  }

  /**
   * Dynamic access of get command via
   *   Conf::[$section]($index, $default = null)
   *
   * Samples:
   *   Conf::get('db', 'host') === Conf::db('host')
   *   Conf::get('db', 'post', '3306') === Conf::db('port', '3306')
   *
   * @param string $name
   *   Section Name
   * @param array $arguments
   *   string Index, varied Default (optional)
   * @return string
   *   Value of INI file or Default if Not Found
   * @throws ConfException
   *   Thrown on setting not found with no $default provided
   */
  public static function __callStatic($name, array $arguments) {
    array_unshift($arguments, $name);
    return call_user_func_array('self::get', $arguments);
  }
}

class ConfException extends Exception {}