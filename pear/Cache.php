<?php

namespace stj;

require_once 'Log.php';
use stj\Log;

/**
 * Prevents repetive use of APC when we can instead hold it in memory
 *
 * @see https://github.com/stjohnjohnson/stj-core-php
 */
class Cache {
  const APC_TIMEOUT = 3600; // Default at 60 minutes

  protected static $_contents = array();

  /**
   * Returns object from APC cache
   *
   * @param string $key
   *   Unique index of the object
   * @return varied|bool
   *   false on failure, value otherwise
   */
  public static function get($key) {
    if (!isset(self::$_contents[$key]) || empty(self::$_contents[$key])) {
      Log::debug('[CACHE] Fetching Key: ' . $key);
      self::$_contents[$key] = apc_fetch($key);
    }

    return self::$_contents[$key];
  }

  /**
   * Stores $value in apc $key for $ttl seconds
   *
   * @param string $key
   *   Unique index of the object
   * @param varied $value
   *   Value of the object
   * @param int $ttl
   *   Eeconds until expiration
   */
  public static function set($key, $value, $ttl = self::APC_TIMEOUT) {
    Log::debug('[CACHE] Setting Key: ' . $key);
    self::$_contents[$key] = $value;
    apc_store($key, $value, $ttl);
  }


  /**
   * Deletes $key in apc and in local cache
   *
   * @param string $key
   *   Unique index of the object
   */
  public static function delete($key) {
    Log::debug("[CACHE] Deleting Key: $key");
    if (isset(self::$_contents[$key])) {
      unset(self::$_contents[$key]);
    }
    apc_delete($key);
  }
}
