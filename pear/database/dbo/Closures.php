<?php

namespace STJ\Database\Dbo;

use Closure;

/**
 * A collection of closure methods designed to take in one value and
 * return true if they pass, false if they do not.
 *
 * @see https://github.com/stjohnjohnson/stj-core-php
 */
class Closures {
  /**
   * Returns true if positive
   *
   * @return Closure
   */
  public static function isPositive() {
    return function ($value)
    {
      return $value >= 0;
    };
  }

  /**
   * Returns true if integer
   *
   * @return Closure
   */
  public static function isInteger() {
    return function ($value)
    {
      // Remove - from in-front of number
      return ctype_digit(ltrim((string) $value, '-'));
    };
  }

  /**
   * Returns true if float
   *
   * @return Closure
   */
  public static function isFloat() {
    return function ($value)
    {
      return preg_match('/^-?\d*\.?\d*$/', (string) $value) === 1;
    };
  }

  /**
   * Returns true if boolean
   *
   * @return Closure
   */
  public static function isBoolean() {
    return function ($value)
    {
      return (is_bool($value) || in_array($value, array(0, 1, '0', '1'), true));
    };
  }

  /**
   * Returns true not null or empty string
   *
   * @param bool $useZero
   *  Use Zero in Check
   * @return Closure
   */
  public static function isValid($useZero = true) {
    return function ($value) use ($useZero)
    {
      if (is_object($value) || is_bool($value)) {
        return true;
      }
      if (is_array($value)) {
        return !empty($value);
      }
      $test = array(null, '');
      if ($useZero) {
        $test[] = '0';
      }
      return !in_array(trim((string)$value), $test);
    };
  }

  /**
   * Returns true if it passes $regex
   *
   * @param string $regex
   *   Regular expression
   * @return Closure
   */
  public static function matchRegex($regex) {
    return function ($value) use ($regex)
    {
      return preg_match($regex, $value) === 1;
    };
  }

  /**
   * Returns true if it is in $array
   *
   * @param array $array
   *   Array to check against
   * @return Closure
   */
  public static function inArray($array) {
    return function ($value) use ($array)
    {
      return in_array($value, $array);
    };
  }

  /**
   * Returns true if all items are in $array
   *
   * @param array $array
   *   Array to validate against
   * @return Closure
   */
  public static function allInArray($array) {
    return function ($value) use ($array)
    {
      // If not array, evaluate as simple
      if (!is_array($value)) {
        return in_array($value, $array);
      }

      foreach ($value as $item) {
        if (!in_array($item, $array)) {
          return false;
        }
      }
      return true;
    };
  }

  /**
   * Returns true if the string length is within a specific length
   *
   * @param int $length
   *   Max length of the string
   * @return Closure
   */
  public static function stringWithinLength($length) {
    return function ($str) use ($length)
    {
      return (strlen($str) <= $length);
    };
  }
}