<?php

namespace STJ\Database\Dbo;

use Exception;

/**
 * Stateful Trackable Objects
 *
 * Allow loading, creating, updating, and deleting of objects to a storage engine
 * 
 * @see https://github.com/stjohnjohnson/stj-core-php
 */
abstract class Stateful extends Trackable {
  // Is the object new
  protected $_new = true;
  // Will the object be deleted on Save
  protected $_deleting = false;

  /**
   * Returns true if the record is new
   *
   * @return bool
   */
  public function isNew() {
    return $this->_new;
  }

  /**
   * Sets the New flag on the record
   *
   * @param bool $new
   *   True for New, False for Old
   * @return Stateful
   */
  public function markAsNew($new = true) {
    if (is_bool($new)) {
      $this->_new = $new;
    }

    return $this;
  }

  /**
   * Is the object going to be deleted
   *
   * @return bool
   */
  public function isDeleting() {
    return $this->_deleting;
  }

  /**
   * Sets the Delete flag on the record
   *
   * @param bool $delete
   *   True for Delete, False for Don't Delete
   * @return Stateful
   */
  public function deleteOnSave($delete = true) {
    if (is_bool($delete)) {
      $this->_deleting = $delete;
    }

    return $this;
  }

  /**
   * Saves the object
   * - Creates if New
   * - Updates if Old
   * - Deletes if Deleting
   *
   * @return type
   */
  public function save() {
    if ($this->isDeleting()) {
      // We're Deleting, so call Delete method with passed args
      return call_user_func_array(array($this, 'delete'), func_get_args());
    } elseif ($this->isNew()) {
      // This object is New, so call Create method with passed args
      return call_user_func_array(array($this, 'create'), func_get_args());
    } else {
      // This object is old, so call Update method with passed args
      return call_user_func_array(array($this, 'update'), func_get_args());
    }
  }

  /**
   * Executes Before, Perform, and After methods of a specific type
   *
   * @param string $method
   *   Name of method
   * @param array $args
   *   Arguments to pass into methods
   * @return Stateful
   */
  private final function _stateLogic($method, array $args) {
    // Call Before Method
    call_user_func_array(array($this, '_before' . $method), $args);
    // Call Perform Method
    call_user_func_array(array($this, '_perform' . $method), $args);
    // Call After Method
    call_user_func_array(array($this, '_after' . $method), $args);

    return $this;
  }

  /**
   * Creates the object into stateful storage
   */
  public function create() {
    // Call _beforeCreate, _performCreate, and _afterCreate
    return $this->_stateLogic('Create', func_get_args());
  }

  /**
   * Executes before Create
   */
  protected function _beforeCreate() { }

  /**
   * Executes after Create
   */
  protected function _afterCreate() { }

  /**
   * Performs Create Logic
   *
   * @throws StatefulException
   *   If not implemented
   */
  protected function _performCreate() {
    throw new StatefulException('Not Implemented: Create', 500);
  }

  /**
   * Creates Many Objects
   *
   * @throws StatefulException
   *   If not implemented
   */
  public static function createMany() {
    throw new StatefulException('Not Implemented: CreateMany', 500);
  }

  /**
   * Updates the stored object
   */
  public function update() {
    // Call _beforeUpdate, _performUpdate, and _afterUpdate
    return $this->_stateLogic('Update', func_get_args());
  }

  /**
   * Executes before Update
   */
  protected function _beforeUpdate() { }

  /**
   * Executes after Update
   */
  protected function _afterUpdate() { }

  /**
   * Performs Update Logic
   *
   * @throws StatefulException
   *   If not implemented
   */
  protected function _performUpdate() {
    throw new StatefulException('Not Implemented: Update', 500);
  }

  /**
   * Updates Many Objects
   *
   * @throws StatefulException
   *   If not implemented
   */
  public static function updateMany() {
    throw new StatefulException('Not Implemented: UpdateMany', 500);
  }

  /**
   * Deletes the stored object
   */
  public function delete() {
    // Call _beforeDelete, _performDelete, and _afterDelete
    return $this->_stateLogic('Delete', func_get_args());
  }

  /**
   * Executes before Delete
   */
  protected function _beforeDelete() { }

  /**
   * Executes after Delete
   */
  protected function _afterDelete() { }

  /**
   * Performs Delete Logic
   *
   * @throws StatefulException
   *   If not implemented
   */
  protected function _performDelete() {
    throw new StatefulException('Not Implemented: Delete', 500);
  }

  /**
   * Deletes Many Objects
   *
   * @throws StatefulException
   *   If not implemented
   */
  public static function deleteMany() {
    throw new StatefulException('Not Implemented: DeleteMany', 500);
  }

  /**
   * Loads the stored object
   */
  public function load() {
    // Call _beforeLoad, _performLoad, and _afterLoad
    return $this->_stateLogic('Load', func_get_args());
  }

  /**
   * Executes before Load
   */
  protected function _beforeLoad() { }

  /**
   * Executes after Load
   */
  protected function _afterLoad() { }

  /**
   * Performs Load Logic
   *
   * @throws StatefulException
   *   If not implemented
   */
  protected function _performLoad() {
    throw new StatefulException('Not Implemented: Load', 500);
  }

  /**
   * Loads Many Objects
   *
   * @throws StatefulException
   *   If not implemented
   */
  public static function loadMany() {
    throw new StatefulException('Not Implemented: LoadMany', 500);
  }
}

class StatefulException extends Exception {}