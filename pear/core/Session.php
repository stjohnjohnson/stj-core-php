<?php

namespace STJ\Core;

/**
 * Simple singleton access to Session data
 *
 * @see https://github.com/stjohnjohnson/stj-core-php
 */
class Session {
  protected static $_init = false;

  /**
   * Initialize Session
   */
  public static function init() {
    // Only init once
    if (!self::$_init) {
      session_start();
      self::$_init = true;
    }
  }

  /**
   * Returns object from Session
   *
   * @param string $key
   *   Unique index of the object
   * @return varied|bool
   *   false on failure, value otherwise
   */
  public static function get($key) {
    // Initialize Session if not already
    self::init();

    return isset($_SESSION[$key]) ? $_SESSION[$key] : false;
  }

  /**
   * Stores $value in Session Object $key
   *
   * @param string $key
   *   Unique index of the object
   * @param varied $value
   *   Value of the object
   */
  public static function set($key, $value) {
    // Initialize Session if not already
    self::init();

    Log::debug('[SESSION] Setting Key: ' . $key);
    $_SESSION[$key] = $value;
  }

  /**
   * Check is variable exists in Session
   *
   * @param string $key
   *   Unique index of the key
   * @return boolean
   */
  public static function has($key) {
    // Initialize Session if not already
    self::init();

    return array_key_exists($key, $_SESSION);
  }

  /**
   * Deletes $key in Session Object
   *
   * @param string $key
   *   Unique index of the object
   */
  public static function delete($key) {
    // Initialize Session if not already
    self::init();

    Log::debug("[SESSION] Deleting Key: $key");
    // Remove from session array
    if (isset($_SESSION[$key])) {
      unset($_SESSION[$key]);
    }
  }
}
