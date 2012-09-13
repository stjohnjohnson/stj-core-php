<?php

namespace STJ\Database\Dbo;

use ReflectionMethod;

/**
 * Stateful Test
 */
class StatefulTest extends \UnitTest {
  /**
   * @test
   * @group Stateful
   * @group Stateful.beforeAfter
   * @covers STJ\Database\Dbo\Stateful
   * @dataProvider beforeAfterProvider
   */
  public function beforeAfter($method) {
    $foo = new StatefulMock();
    $beforeMethod = new ReflectionMethod($foo, '_before' . $method);
    $beforeMethod->setAccessible(true);
    $afterMethod = new ReflectionMethod($foo, '_after' . $method);
    $afterMethod->setAccessible(true);

    $this->assertEquals(null, $beforeMethod->invoke($foo));
    $this->assertEquals(null, $afterMethod->invoke($foo));
  }

  /**
   * Data Provider for beforeAfter test
   *
   * @return array
   */
  public function beforeAfterProvider() {
    $cases = array();

    $cases[] = array('Load');
    $cases[] = array('Create');
    $cases[] = array('Update');
    $cases[] = array('Delete');

    return $cases;
  }

  /**
   * @test
   * @group Stateful
   * @group Stateful.missingMethods
   * @covers STJ\Database\Dbo\Stateful
   * @dataProvider missingProvider
   */
  public function missingMethods($method) {
    $this->setExpectedException('STJ\\Database\\Dbo\\StatefulException', 'Not Implemented: ' . ucfirst($method));

    $foo = new StatefulMock();
    $foo->$method();
  }

  /**
   * Data Provider for missingMethods test
   *
   * @return array
   */
  public function missingProvider() {
    $cases = array();

    $cases[] = array('Load');
    $cases[] = array('Create');
    $cases[] = array('Update');
    $cases[] = array('Delete');

    $cases[] = array('loadMany');
    $cases[] = array('createMany');
    $cases[] = array('updateMany');
    $cases[] = array('deleteMany');


    return $cases;
  }

  /**
   * @test
   * @group Stateful
   * @group Stateful.stateLogic
   * @covers STJ\Database\Dbo\Stateful::_stateLogic
   * @dataProvider beforeAfterProvider
   */
  public function stateLogic($name) {
    $foo = new StatefulMockTracker();
    $method = new ReflectionMethod($foo, '_stateLogic');
    $method->setAccessible(true);

    $foo->reset();
    $method->invoke($foo, strtolower($name), array(1, true, 'blue'));

    $this->assertEquals(array(
      'STJ\\Database\\Dbo\\StatefulMockTracker::_before' . $name => array(array(1, true, 'blue')),
      'STJ\\Database\\Dbo\\StatefulMockTracker::_perform' . $name => array(array(1, true, 'blue')),
      'STJ\\Database\\Dbo\\StatefulMockTracker::_after' . $name => array(array(1, true, 'blue')),
    ), StatefulMockTracker::$calls);
  }

  /**
   * @test
   * @group Stateful
   * @group Stateful.saveCreate
   * @covers STJ\Database\Dbo\Stateful::save
   */
  public function saveCreate() {
    $foo = new StatefulMockTracker();
    $foo->reset();

    $foo->save(__METHOD__);
    $this->assertEquals(array(
      'STJ\\Database\\Dbo\\StatefulMockTracker::_beforeCreate' => array(array(__METHOD__)),
      'STJ\\Database\\Dbo\\StatefulMockTracker::_performCreate' => array(array(__METHOD__)),
      'STJ\\Database\\Dbo\\StatefulMockTracker::_afterCreate' => array(array(__METHOD__)),
    ), StatefulMockTracker::$calls);
  }

  /**
   * @test
   * @group Stateful
   * @group Stateful.saveUpdate
   * @covers STJ\Database\Dbo\Stateful::save
   */
  public function saveUpdate() {
    $foo = new StatefulMockTracker();
    $foo->reset();
    $foo->markAsNew(false);

    $foo->save(__METHOD__);
    $this->assertEquals(array(
      'STJ\\Database\\Dbo\\StatefulMockTracker::_beforeUpdate' => array(array(__METHOD__)),
      'STJ\\Database\\Dbo\\StatefulMockTracker::_performUpdate' => array(array(__METHOD__)),
      'STJ\\Database\\Dbo\\StatefulMockTracker::_afterUpdate' => array(array(__METHOD__)),
    ), StatefulMockTracker::$calls);
  }

  /**
   * @test
   * @group Stateful
   * @group Stateful.saveDelete
   * @covers STJ\Database\Dbo\Stateful::save
   */
  public function saveDelete() {
    $foo = new StatefulMockTracker();
    $foo->reset();
    $foo->deleteOnSave(true);

    $foo->save(__METHOD__);
    $this->assertEquals(array(
      'STJ\\Database\\Dbo\\StatefulMockTracker::_beforeDelete' => array(array(__METHOD__)),
      'STJ\\Database\\Dbo\\StatefulMockTracker::_performDelete' => array(array(__METHOD__)),
      'STJ\\Database\\Dbo\\StatefulMockTracker::_afterDelete' => array(array(__METHOD__)),
    ), StatefulMockTracker::$calls);
  }

  /**
   * @test
   * @group Stateful
   * @group Stateful.flags
   * @covers STJ\Database\Dbo\Stateful
   */
  public function flags() {
    $foo = new StatefulMock();

    // New tests
    $this->assertTrue($foo->isNew());
    $foo->markAsNew(false);
    $this->assertFalse($foo->isNew());
    $foo->markAsNew('Garbage');
    $this->assertFalse($foo->isNew());

    // Delete tests
    $this->assertFalse($foo->isDeleting());
    $foo->deleteOnSave(true);
    $this->assertTrue($foo->isDeleting());
    $foo->deleteOnSave('Garbage');
    $this->assertTrue($foo->isDeleting());
  }
}

class StatefulMock extends Stateful {
  public function isProperty($property) {
    return true;
  }
}

class StatefulMockTracker extends StatefulMock {
  public static $calls = array();

  public static function reset() {
    self::$calls = array();
  }

  protected function _beforeCreate() {
    self::$calls[__METHOD__][] = func_get_args();
  }
  protected function _afterCreate() {
    self::$calls[__METHOD__][] = func_get_args();
  }
  protected function _performCreate() {
    self::$calls[__METHOD__][] = func_get_args();
  }
  public static function createMany() {
    self::$calls[__METHOD__][] = func_get_args();
  }
  protected function _beforeUpdate() {
    self::$calls[__METHOD__][] = func_get_args();
  }
  protected function _afterUpdate() {
    self::$calls[__METHOD__][] = func_get_args();
  }
  protected function _performUpdate() {
    self::$calls[__METHOD__][] = func_get_args();
  }
  public static function updateMany() {
    self::$calls[__METHOD__][] = func_get_args();
  }
  protected function _beforeDelete() {
    self::$calls[__METHOD__][] = func_get_args();
  }
  protected function _afterDelete() {
    self::$calls[__METHOD__][] = func_get_args();
  }
  protected function _performDelete() {
    self::$calls[__METHOD__][] = func_get_args();
  }
  public static function deleteMany() {
    self::$calls[__METHOD__][] = func_get_args();
  }
  protected function _beforeLoad() {
    self::$calls[__METHOD__][] = func_get_args();
  }
  protected function _afterLoad() {
    self::$calls[__METHOD__][] = func_get_args();
  }
  protected function _performLoad() {
    self::$calls[__METHOD__][] = func_get_args();
  }
  public static function loadMany() {
    self::$calls[__METHOD__][] = func_get_args();
  }
}