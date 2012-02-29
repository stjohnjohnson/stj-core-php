<?php

namespace stj;

/**
 * Auto-magically determines Path/Filename based on Class
 *
 * @see https://github.com/stjohnjohnson/stj-core-php
 */
class Autoload {
  /**
   * Automatically includes file based on class name, example:
   * \test\Sample => test/Sample.php
   *
   * Automatically tries different cases.
   *
   * @param string $class
   *   Class you are looking to load
   * @return boolean
   *   Loaded Successfully
   */
  public static function load($class) {
    $filename = static::classToFile($class, '.php');

    if ($filename !== false) {
      include $filename;
      return true;
    }

    return false;
  }

  /**
   * Converts class and filending to file
   *
   * @param string $class
   *   Class you are loading
   * @param string $fileEnding
   *   File Ending of the file
   * @return string|boolean
   *   filename on success, false on failure
   */
  public static function classToFile($class, $fileEnding = '.php') {
    // We want the path and class seperate
    $namespace = explode('\\', $class);
    $classname = array_pop($namespace);

    // Compress namespace back
    $namespace = implode('/', $namespace);

    // First try, check for standard folder structure
    // Second try, lowercase folder structure - Class is still initial case
    // Final try, all lowercase
    $files = array(
        $namespace . '/' . $classname . $fileEnding,
        strtolower($namespace) . '/' . $classname . $fileEnding,
        strtolower($namespace) . '/' . strtolower($classname) . $fileEnding
    );

    // Check if each of the files exist
    foreach ($files as $filename) {
      $filename = ltrim($filename, '/');
      if (static::_isFile($filename)) {
        return $filename;
      }
    }

    return false;
  }

  /**
   * This leverages the include-path ini setting (used in Require/Include) to
   * validate if a file exists or not.
   *
   * @see http://www.php.net/manual/en/ini.core.php#ini.include-path
   * @param string $filename
   *   Filename to check
   * @return boolean
   */
  protected static function _isFile($filename) {
    $pointer = @fopen($filename, 'r', true);
    if ($pointer === false) {
      return false;
    }
    fclose($pointer);
    return true;
  }
}
