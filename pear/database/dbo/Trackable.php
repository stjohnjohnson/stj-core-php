<?php

namespace STJ\Database\Dbo;

use Exception;

/**
 * Smart Object that keeps track of properties that have changed
 */
abstract class Trackable {
  // Clean values (from the DB)
  protected $_clean = array();
  // Dirty values (from the object)
  protected $_dirty = array();
  // Other data that do not have a DB field
  protected $_others = array();
  // Changes to fields (add / subtract)
  protected $_shifts = array();

  /**
   * Creates new Model object (and sets properties)
   *
   * @param array $properties
   */
  public function __construct(array $properties = array()) {
    $this->setFromArray($properties);
  }

  /**
   * Magic Method for checking if a property is set
   *
   * @param string $property
   *   Property name
   * @return bool
   *   True if exists, False otherwise
   */
  public function __isset($property) {
    // Check dirty, clean, and others
    // @note Dirty returns true for null, not for clean and others
    if (array_key_exists($property, $this->_dirty)) {
      return true;
    } elseif (isset($this->_clean[$property])) {
      return true;
    } elseif (isset($this->_others[$property])) {
      return true;
    }

    return false;
  }

  /**
   * Magic Method for removing a property
   *
   * @param string $property
   *   Property name
   */
  public function __unset($property) {
    // Set to null
    $this->set($property, null);
  }

  /**
   * Magic Method for setting properties
   *
   * @param string $property
   *   Property name
   * @param varied $value
   *   Value to set
   */
  public function __set($property, $value) {
    $this->set($property, $value);
  }

  /**
   * Magic Method for getting the value of a property
   *
   * @param string $property
   *   Property name
   * @return varied|null
   *   Value of property or null if not found
   */
  public function &__get($property) {
    return $this->get($property);
  }

  /**
   * Expands key/value array into object properties
   *
   * @param array $array
   *   property => value
   * @return Model
   */
  public function setFromArray(array $array) {
    foreach ($array as $key => $value) {
      $this->set($key, $value);
    }

    return $this;
  }

  /**
   * Set a property to a value
   *
   * @param string $property
   *   Property name (can contain modifiers 'foo:+' 'foo:-')
   * @param varied $value
   *   Value to set
   * @return Model
   */
  public function set($property, $value) {
    // Check if we manage it
    if ($this->isProperty($property)) {
      $old = $this->get($property, true);
      $new = $value;

      // Compare numbers as strings
      if (is_numeric($value)) {
        $new = (string)$value;
      }

      // Clear shift
      unset($this->_shifts[$property]);

      // Check if dirty
      if ($old !== $new) {
        $this->_dirty[$property] = $value;
      } else {
        unset($this->_dirty[$property]);
      }
    } else {
      $this->_others[$property] = $value;
    }

    return $this;
  }

  /**
   * Add value to a property
   *
   * @param string $property
   *   Property name
   * @param int $value
   *   Number to add to value
   * @return \STJ\Database\Dbo\Trackable
   * @throws TrackableException on non-numeric value
   */
  public function add($property, $value) {
    // Get old value
    $old = $this->get($property, true);
    if ($old === null) {
      $old = 0;
    }

    // Ensure we're only adding numbers to numbers (or null)
    if (!is_numeric($value) || !is_numeric($old)) {
      throw new TrackableException("Cannot add '$value' to '$old' on '$property'");
    }

    // Calculate new value
    $new = $old + $value;

    // Store change
    if ($this->isProperty($property)) {
      $this->_dirty[$property] = $new;
      $this->_shifts[$property] = '+:' . $value;
    } else {
      $this->_others[$property] = $new;
    }

    return $this;
  }

  /**
   * Subtracts value from a property
   *
   * @param string $property
   *   Property name
   * @param int $value
   *   Number to remove from value
   * @return \STJ\Database\Dbo\Trackable
   * @throws TrackableException on non-numeric value
   */
  public function sub($property, $value) {
    // Get old value
    $old = $this->get($property, true);
    if ($old === null) {
      $old = 0;
    }

    // Ensure we're only subtracting numbers from numbers (or null)
    if (!is_numeric($value) || !is_numeric($old)) {
      throw new TrackableException("Cannot subtract '$value' from '$old' on '$property'");
    }

    // Calculate new value
    $new = $old - $value;

    // Store change
    if ($this->isProperty($property)) {
      $this->_dirty[$property] = $new;
      $this->_shifts[$property] = '-:' . $value;
    } else {
      $this->_others[$property] = $new;
    }

    return $this;
  }

  /**
   * Change a property with a value (=,+,-)
   *
   * @param string $property
   *   Property name (can contain modifiers 'foo:+' 'foo:-')
   * @param varied $value
   *   Value to affect the property
   * @return Model
   * @throws TrackableException on invalid modifier
   */
  public function change($property, $value) {
    // Check if we have a modifier
    $modifier = '=';
    if (strpos($property, ':')) {
      list($property, $modifier) = explode(':', $property, 2);
    }

    // Switch based on the modifier
    switch ($modifier) {
      // Add value to a property
      case '+':
      case 'add':
        $this->add($property, $value);
        break;

      // Subtract value from a property
      case '-':
      case 'sub':
        $this->sub($property, $value);
        break;

      // Set a property's value
      case '=':
      case 'set':
        $this->set($property, $value);
        break;

      default:
        throw new TrackableException("Unknown Modifier: '$modifier'");
    }

    return $this;
  }

  /**
   * Gets the value of a property
   *
   * @param string $property
   *   Property name
   * @param bool $clean
   *   Return only 'clean' values (not dirty)
   * @return varied
   */
  public function &get($property, $clean = false) {
    // @note Dirty returns if null, not for clean and others
    if (array_key_exists($property, $this->_dirty) && !$clean) {
      // Check if we have this value in Dirty
      return $this->_dirty[$property];
    } elseif (isset($this->_clean[$property])) {
      // Check if we have this value in clean values
      return $this->_clean[$property];
    } elseif (isset($this->_others[$property])) {
      // Check if it's personally declared
      return $this->_others[$property];
    }

    // Otherwise return null
    $value = null;

    return $value;
  }

  /**
   * Is this a trackable property
   *
   * @param string $property
   *   Property name
   * @return bool
   *   False if not a property
   */
  abstract public function isProperty($property);

  /**
   * Returns true if the property is shifting (+/-)
   *
   * @param string $property
   *   Property name
   * @return bool
   */
  public function isPropertyShifting($property) {
    if (array_key_exists($property, $this->_shifts)) {
      return true;
    }

    return false;
  }

  /**
   * Returns true if the property has changed
   *
   * @param string $property
   *   Property name
   * @return string|null
   *   Null if not found
   */
  public function getPropertyShift($property) {
    if (array_key_exists($property, $this->_shifts)) {
      return $this->_shifts[$property];
    }

    return null;
  }

  /**
   * Returns true if the property has changed
   *
   * @param string $property
   *   Property name
   * @return bool
   */
  public function hasPropertyChanged($property) {
    if (array_key_exists($property, $this->_dirty)) {
      return true;
    }

    return false;
  }

  /**
   * Returns true if any of the properties have changed
   *
   * @param array $properties
   *   List of properties to check
   * @return bool
   */
  public function havePropertiesChanged($properties) {
    foreach ($properties as $property) {
      if (array_key_exists($property, $this->_dirty)) {
        return true;
      }
    }

    return false;
  }

  /**
   * Returns the list of fields that have been dirtied
   *
   * @return array
   */
  public function getChangedProperties() {
    return array_keys($this->_dirty);
  }

  /**
   * Clears dirty flag on a property
   *
   * @param string $property
   *   Property to clear
   * @return Trackable
   */
  public function clearChangedProperty($property) {
    unset($this->_dirty[$property]);
    unset($this->_shifts[$property]);

    return $this;
  }

  /**
   * Clears all dirty flags
   *
   * @return Trackable
   */
  public function resetChangedProperties() {
    $this->_dirty = array();
    $this->_shifts = array();

    return $this;
  }

  /**
   * Migrates all dirty values to clean values
   *
   * @return Trackable
   */
  protected function _migrateDirtyToClean() {
    foreach ($this->_dirty as $field => $value) {
      $this->_clean[$field] = $value;
    }

    return $this->resetChangedProperties();
  }

  /**
   * Export all fields to an array
   *
   * @return array
   *   Hash of fields
   */
  public function toArray($all = false) {
    // Get all fields
    $array = array_merge($this->_clean, $this->_dirty);

    // If we're returning all, add others
    if ($all) {
      $array = array_merge($array, $this->_others);
    }

    return $array;
  }
}

class TrackableException extends Exception {}