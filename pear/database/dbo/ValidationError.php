<?php

namespace STJ\Database\Dbo;

use Exception;

/**
 * Validation Error
 *
 * Providing specific errors per field
 * 
 * @see https://github.com/stjohnjohnson/stj-core-php
 */
class ValidationError {
  protected $_errors = array();

  /**
   * Add an Error for $field with $message
   *
   * @param string $field
   *   Field in question
   * @param string|array $message
   *   Error message associated with that field
   * @return ValidationError
   */
  public function __set($field, $message) {
    // Support array of messages
    if (is_array($message)) {
      foreach ($message as $msg) {
        $this->__set($field, $msg);
      }
    } else {
      if (isset($this->_errors[$field])) {
        $this->_errors[$field][] = $message;
      } else {
        $this->_errors[$field] = array($message);
      }
    }

    return $this;
  }

  /**
   * List the messages for a $field
   *
   * @param string $field
   *   Field in question
   * @return array
   *   Messages about that field
   */
  public function __get($field) {
    if (isset($this->_errors[$field])) {
      return $this->_errors[$field];
    } else {
      return array();
    }
  }

  /**
   * Are there messages stored for $field
   *
   * @param type $field
   *   Field in question
   * @return bool
   *   Is the field set
   */
  public function __isset($field) {
    return isset($this->_errors[$field]);
  }

  /**
   * Import errors from $array
   *
   * @param array $array
   *   Array of errors in $field => array($messages) format
   * @return ValidationError
   */
  public function fromArray(array $array) {
    foreach ($array as $key => $value) {
      $this->__set($key, $value);
    }

    return $this;
  }

  /**
   * Export errors to array
   *
   * @return array
   *   Array of $field => array($messages)
   */
  public function toArray() {
    return $this->_errors;
  }

  /**
   * Merge ValidationError $from into this ValidationError
   *
   * @return ValidationError
   */
  public function merge(ValidationError $from) {
    return $this->fromArray($from->toArray());
  }

  /**
   * Returns true if no errors
   *
   * @return bool
   */
  public function isEmpty() {
    return empty($this->_errors);
  }

  /**
   * Generates an Exception with this data
   *
   * @return ValidationException
   */
  public function toException() {
    // Reduce the array of arrays to a simple array of messages
    $messages = array_reduce(array_values($this->_errors), 'array_merge', array());

    // Throw into an exception
    $ex = new ValidationException(implode(PHP_EOL, $messages), 400);

    // Append the raw data
    $ex->errors = $this->_errors;

    return $ex;
  }
}

class ValidationException extends Exception {}