<?php

namespace STJ\Database\Dbo;

use Exception,
    Closure;

/**
 * Perform simple validation on the object before saving (or other operations)
 *
 * @see https://github.com/stjohnjohnson/stj-core-php
 */
abstract class Validated extends RowBased {
  /**
   * Save the object
   *
   * @param array $validators
   *   List of Methods (that start with _check)
   * @return Validated
   */
  public function save($validators = null) {
    $this->validate($validators);
    return parent::save();
  }

  /**
   * Runs validation methods against the current object
   *
   * Optionally pass array of specific methods to run
   *
   * @param array $validators
   *   List of Methods (that start with _check)
   * @return Validated
   */
  public function validate($validators = null) {
    // check methods storage
    $available = array_merge(
            get_class_methods(get_parent_class($this)),
            get_class_methods($this)
    );

    foreach ($available as $k => $method) {
      // Remove methods that don't start with _check
      if (strncmp($method, '_check', 5) !== 0) {
        unset($available[$k]);
      }
    }

    $methods = array_flip($available);

    // make sure the required properties are validated last to allow for other
    // checks to set properties before checking if they are present
    unset($methods['_checkRequiredProperties']);
    $methods['_checkRequiredProperties'] = true;

    // transform array keys into values
    $methods = array_keys($methods);

    if ($validators === null || !is_array($validators)) {
      // If we didn't pass any, run ALL!
      $validators = $methods;
    } else {
      // Ensure only valid items
      $validators = array_intersect($methods, $validators);
    }

    $errors = new ValidationError();

    // Loop through each function, checking for errors
    foreach ($validators as $function) {
      if (method_exists($this, $function)) {
        $response = $this->$function();

        if (is_array($response)) {
          $errors->fromArray($response);
        } elseif (is_a($response, get_class($errors))) {
          $errors->merge($response);
        }
      }
    }

    // If we have a problem, throw an exception
    if (!$errors->isEmpty()) {
      throw $errors->toException();
    }

    return $this;
  }

  /**
   * Validates properties using a specific method displaying a specific message
   *
   * Closure should follow this format:
   *  - function($value):bool
   *  - true will skip, false will display message
   *
   * Message is for sprintf with %s referring to field's title
   *
   * @param array $properties
   *   In the format field => title
   * @param Closure $method
   *   Closure to execute
   * @param string $message
   *   Message to be recorded
   * @return ValidationError
   */
  public function validateProperties(array $properties, Closure $method, $message) {
    $errors = new ValidationError();

    foreach ($properties as $property) {
      if (!$method($this->$property)) {
        $errors->$property = sprintf($message, $property); // getDesc
      }
    }

    return $errors;
  }

  /**
   * Validates a property using a specific method displaying a specific message
   *
   * Closure should follow this format:
   *  - function($value):bool
   *  - true will skip, false will display message
   *
   * Message is for sprintf with %s referring to field's title
   *
   * @param string $property
   *   Property to check
   * @param Closure $method
   *   Closure to execute
   * @param string $message
   *   Message to be recorded
   * @return ValidationError
   */
  public function validateProperty($property, Closure $method, $message) {
    return $this->validateProperties(array($property), $method, $message);
  }

  /**
   * Checks for required fields
   *
   * @return ValidationError
   */
  protected function _checkRequiredProperties() {
    return $this->validateProperties(
            $this->_getRequiredProperties(),
            Closures::isValid(), '%s is Missing'
    );
  }

  /**
   * Stores the list of required properties
   *
   * @return array
   *   List of properties to be required
   */
  protected function _getRequiredProperties() {
    return array();
  }

  /**
   * Checks currently set values against their database types
   *
   * @return ValidationError
   */
  protected function _checkDataTypes() {
    $class = get_class($this);
    $types = array('int' => array(), 'float' => array(), 'unsigned' => array(), 'tinyint' => array());
    $errors = new ValidationError();

    // Scan the types
    foreach ($this->getProperties() as $property) {
      // Skip empty/non-dirty properties
      if (!isset($this->$property) || !$this->hasPropertyChanged($property)) {
        continue;
      }

      $type = self::_getPropertyType($property, $class);

      // Check for int or timestamp (both are integers)
      if (strpos($type, 'int') === 0 ||
          strpos($type, 'timestamp') === 0) {
        $types['int'][] = $property;
      }

      // Check for float
      if (strpos($type, 'float') === 0) {
        $types['float'][] = $property;
      }

      // Check for unsigned or tiemstamp (both are unsigned)
      if (strpos($type, 'unsigned') !== false ||
          strpos($type, 'timestamp') === 0) {
        $types['unsigned'][] = $property;
      }

      // Check for tinyint/bool
      if (strpos($type, 'tinyint') === 0) {
        $types['tinyint'][] = $property;
      }

      // Check for enum properties
      if (strpos($type, 'enum') === 0) {
        $errors->merge($this->validateProperties(array($property), Closures::inArray(self::_getPropertyOptions($property, $class)),
                                  '%s is an Invalid Option'));
      }

      // Check for set properties
      if (strpos($type, 'set') === 0) {
        $errors->merge($this->validateProperties(array($property), Closures::allInArray(self::_getPropertyOptions($property, $class)),
                                  '%s contains an Invalid Option'));
      }

      // Check for varchar properties
      if (strpos($type, 'varchar') === 0) {
        // Extract the varchar size
        $matches = array();
        preg_match("/^varchar\((?P<varchar_size>[0-9]+)\)/",$type, $matches);
        $varchar_size = isset($matches['varchar_size']) ? $matches['varchar_size'] : 0;
        $errors->merge($this->validateProperties(array($property), Closures::stringWithinLength($varchar_size),
                                  "%s must be within $varchar_size characters"));
      }
    }

    // Now run the field validator against each type
    $errors->merge($this->validateProperties($types['int'],
                Closures::isInteger(), '%s should be an Integer'));
    $errors->merge($this->validateProperties($types['float'],
                Closures::isFloat(), '%s should be a Float'));
    $errors->merge($this->validateProperties($types['unsigned'],
                Closures::isPositive(), '%s should be Positive'));
    $errors->merge($this->validateProperties($types['tinyint'],
                Closures::isBoolean(), '%s should be Boolean'));
    return $errors;
  }
}

class ValidatedException extends Exception {}
