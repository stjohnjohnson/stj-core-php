<?php

namespace stj;

/**
 * Dead Simple Logging mechanism
 *
 * This class allows you to cleanly and manually log:
 * debug/query/info/warnings/errors/exceptions thrown throughout
 * the course of your PHP script.
 *
 * @see https://github.com/stjohnjohnson/stj-core-php
 */
class Log {
  const DISABLED = 0;
  const ERROR = 1;
  const WARNING = 2;
  const INFO = 3;
  const DEBUG = 4;
  const QUERY = 5;

  // Default at Warning mode
  private static $_LIMIT = self::WARNING;
  private static $_LABELS = array(
      self::ERROR => 'ERROR',
    self::WARNING => 'WARNING',
       self::INFO => 'INFO',
      self::DEBUG => 'DEBUG',
      self::QUERY => 'QUERY'
  );

  /**
   * Set Display Limit
   *
   * @param int $limit
   *   One of the constants in this class
   */
  public static function setLimit($limit) {
    // Ensure we're setting a valid mode
    if (isset(self::$_LABELS[$limit]) || $limit === self::DISABLED) {
      self::$_LIMIT = $limit;
    }
  }

  /**
   * Get Display Limit
   *
   * @return int
   *   Limit that is currently set
   */
  public static function getLimit() {
    return self::$_LIMIT;
  }

  /**
   * Write ERROR message to the log
   *
   * @param varied $object
   *   Message to send to log
   */
  public static function error($object) {
    self::_write(self::ERROR, $object);
  }

  /**
   * Write WARNING message to the log
   *
   * @param varied $object
   *   Message to send to log
   */
  public static function warning($object) {
    self::_write(self::WARNING, $object);
  }

  /**
   * Write INFO message to the log
   *
   * @param varied $object
   *   Message to send to log
   */
  public static function info($object) {
    self::_write(self::INFO, $object);
  }

  /**
   * Write DEBUG message to the log
   *
   * @param varied $object
   *   Message to send to log
   */
  public static function debug($object) {
    self::_write(self::DEBUG, $object);
  }

  /**
   * Write QUERY message to the log
   *
   * @param varied $object
   *   Message to send to log
   */
  public static function query($object) {
    self::_write(self::QUERY, $object);
  }

  /**
   * Write in the internal log
   *
   * @param int $level
   *   Level of verboseness
   * @param varied $object
   *   Message to send to the log
   */
  private static function _write($level, $object) {
    // Skip if the message is too verbose
    if ($level > self::$_LIMIT) {
      return;
    }

    $location = debug_backtrace();

    $file = $location[1]['file'];
    $line = $location[1]['line'];

    // Convert message to string
    if (is_a($object, 'Exception')) {
      $message = '[' . get_class($object) . ':' . $object->getCode() . '] ' . $object->getMessage();
      $file = $object->getFile();
      $line = $object->getLine();
    } elseif (is_array($object) || is_object($object)) {
      $message = var_export($object, true);
    } else {
      $message = print_r($object, true);
    }

    // Append the file/line only on warnings or greater
    if ($level <= self::WARNING) {
      $message .= " (file: $file @ line: $line)";

      // Add trace to the end of the message
      if (is_a($object, 'Exception')) {
        $message .= PHP_EOL . $object->getTraceAsString();
      }
    }

    // Write to local log
    error_log("[" . self::$_LABELS[$level] . "] $message");
  }
}