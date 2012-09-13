<?php

namespace STJ\Database\Dbo;

/**
 * Trackable Test
 */
class TrackableTest extends \UnitTest {
  /**
   * @test
   * @group Trackable
   * @group Trackable.magicMethods
   * @covers STJ\Database\Dbo\Trackable
   * @dataProvider allProvider
   */
  public function magicMethods($property, $value1, $value2) {
    $foo = new TrackableMock();

    // Not set yet
    $this->assertFalse(isset($foo->$property));
    $this->assertEquals(null, $foo->$property);

    // Set property
    $foo->$property = $value1;

    $this->assertTrue(isset($foo->$property));
    $this->assertEquals($value1, $foo->$property);

    // Set property
    $foo->$property = $value2;

    $this->assertTrue(isset($foo->$property));
    $this->assertEquals($value2, $foo->$property);

    // Remove
    unset($foo->$property);
    $this->assertFalse(isset($foo->$property));
    $this->assertEquals(null, $foo->$property);
  }

  /**
   * @test
   * @group Trackable
   * @group Trackable.magicMethods
   * @covers STJ\Database\Dbo\Trackable
   * @dataProvider allProvider
   */
  public function oopMethods($property, $value1, $value2) {
    $foo = new TrackableMock();

    // Not set yet
    $this->assertFalse(isset($foo->$property));
    $this->assertEquals(null, $foo->get($property));

    // Set value1
    $foo->set($property, $value1);
    $this->assertTrue(isset($foo->$property));
    $this->assertEquals($value1, $foo->get($property));

    // Set value2
    $foo->set($property, $value2);
    $this->assertTrue(isset($foo->$property));
    $this->assertEquals($value2, $foo->get($property));

    // Remove
    unset($foo->$property);
    $this->assertFalse(isset($foo->$property));
    $this->assertEquals(null, $foo->get($property));
  }

  /**
   * @test
   * @group Trackable
   * @group Trackable.cleanDirtyFlags
   * @covers STJ\Database\Dbo\Trackable
   * @dataProvider propertyProvider
   */
  public function cleanDirtyFlags($property, $value1, $value2) {
    $foo = new TrackableMock();

    // Not dirty
    $this->assertFalse($foo->hasPropertyChanged($property));

    // Set value1
    $foo->set($property, $value1);
    $this->assertTrue(isset($foo->$property));
    $this->assertEquals($value1, $foo->get($property));

    // Dirty
    $this->assertTrue($foo->hasPropertyChanged($property));
    $this->assertTrue($foo->havePropertiesChanged(array($property)));
    $this->assertContains($property, $foo->getChangedProperties());

    // Move to clean
    $foo->clean();
    // Check it exists
    $this->assertTrue(isset($foo->$property));
    $this->assertEquals($value1, $foo->get($property));

    // Not dirty
    $this->assertFalse($foo->hasPropertyChanged($property));
    $this->assertFalse($foo->havePropertiesChanged(array($property)));
    $this->assertNotContains($property, $foo->getChangedProperties());

    // Set value2
    $foo->set($property, $value2);
    $this->assertTrue(isset($foo->$property));
    $this->assertEquals($value2, $foo->get($property));

    // Dirty
    $this->assertTrue($foo->hasPropertyChanged($property));
    $this->assertTrue($foo->havePropertiesChanged(array($property)));
    $this->assertContains($property, $foo->getChangedProperties());

    // Get old value
    $this->assertEquals($value1, $foo->get($property, true));

    // Clear flag
    $foo->clearChangedProperty($property);

    // Check it exists
    $this->assertTrue(isset($foo->$property));
    $this->assertEquals($value1, $foo->get($property));

    // Not dirty
    $this->assertFalse($foo->hasPropertyChanged($property));
    $this->assertFalse($foo->havePropertiesChanged(array($property)));
    $this->assertNotContains($property, $foo->getChangedProperties());
  }

  /**
   * @test
   * @group Trackable
   * @group Trackable.construct
   * @covers STJ\Database\Dbo\Trackable
   */
  public function construct() {
    $foo = new TrackableMock();

    $this->assertFalse(isset($foo->bob));
    $this->assertFalse(isset($foo->jane));

    $foo = new TrackableMock(array('bob' => 15, 'jane' => 35));
    $this->assertTrue(isset($foo->bob));
    $this->assertEquals(15, $foo->bob);
    $this->assertEquals(15, $foo->exposeValueOther('bob'));
    $this->assertTrue(isset($foo->jane));
    $this->assertEquals(35, $foo->jane);
    $this->assertEquals(35, $foo->exposeValueOther('jane'));
  }

  /**
   * @test
   * @group Trackable
   * @group Trackable.setFromArray
   * @covers STJ\Database\Dbo\Trackable
   */
  public function setFromArray() {
    $foo = new TrackableMock();

    $this->assertFalse(isset($foo->bob));
    $this->assertFalse(isset($foo->jane));

    $foo->setFromArray(array('bob' => 15, 'jane' => 35));
    $this->assertTrue(isset($foo->bob));
    $this->assertEquals(15, $foo->bob);
    $this->assertEquals(15, $foo->exposeValueOther('bob'));
    $this->assertTrue(isset($foo->jane));
    $this->assertEquals(35, $foo->jane);
    $this->assertEquals(35, $foo->exposeValueOther('jane'));
  }

  /**
   * @test
   * @group Trackable
   * @group Trackable.toArray
   * @covers STJ\Database\Dbo\Trackable
   */
  public function toArray() {
    $array = array('a' => 'hi', 'bob' => 15, 'jane' => 35);
    $foo = new TrackableMock();

    // Set values
    $foo->setFromArray($array);
    $foo->clean();
    $foo->a = 'toast';
    $foo->b = 12;

    // Only see fields
    $this->assertEquals(array('a' => 'toast', 'b' => 12), $foo->toArray());

    // See all fields
    $this->assertEquals(array('a' => 'toast', 'b' => 12, 'bob' => 15, 'jane' => 35), $foo->toArray(true));
  }

  /**
   * @test
   * @group Trackable
   * @group Trackable.addValue
   * @covers STJ\Database\Dbo\Trackable
   * @dataProvider shiftProvider
   */
  public function addValue($property, $old, $diff, $exception = false) {
    $foo = new TrackableMock();

    $foo->set($property, $old);
    $this->assertEquals($old, $foo->get($property));
    $this->assertFalse($foo->isPropertyShifting($property));
    $foo->clean();

    if ($exception) {
      $this->setExpectedException('STJ\\Database\\Dbo\\TrackableException', "Cannot add '$diff' to '$old' on '$property'");
    }

    $foo->add($property, $diff);
    $this->assertEquals($old + $diff, $foo->get($property));
    if ($foo->isProperty($property)) {
      $this->assertTrue($foo->isPropertyShifting($property));
      $this->assertEquals("+:$diff", $foo->getPropertyShift($property));
    } else {
      $this->assertFalse($foo->isPropertyShifting($property));
      $this->assertEquals(null, $foo->getPropertyShift($property));
    }
  }

  /**
   * @test
   * @group Trackable
   * @group Trackable.subValue
   * @covers STJ\Database\Dbo\Trackable
   * @dataProvider shiftProvider
   */
  public function subValue($property, $old, $diff, $exception = false) {
    $foo = new TrackableMock();

    $foo->set($property, $old);
    $this->assertEquals($old, $foo->get($property));
    $this->assertFalse($foo->isPropertyShifting($property));
    $foo->clean();

    if ($exception) {
      $this->setExpectedException('STJ\\Database\\Dbo\\TrackableException', "Cannot subtract '$diff' from '$old' on '$property'");
    }

    $foo->sub($property, $diff);
    $this->assertEquals($old - $diff, $foo->get($property));
    if ($foo->isProperty($property)) {
      $this->assertTrue($foo->isPropertyShifting($property));
      $this->assertEquals("-:$diff", $foo->getPropertyShift($property));
    } else {
      $this->assertFalse($foo->isPropertyShifting($property));
      $this->assertEquals(null, $foo->getPropertyShift($property));
    }
  }

  /**
   * @test
   * @group Trackable
   * @group Trackable.setModifier
   * @covers STJ\Database\Dbo\Trackable
   * @dataProvider modifierProvider
   */
  public function setModifier($property, $old, $diff, $new, $modifier, $exception = false) {
    $foo = new TrackableMock();

    $foo->set($property, $old);
    $this->assertEquals($old, $foo->get($property));
    $foo->clean();

    if ($exception) {
      $this->setExpectedException('STJ\\Database\\Dbo\\TrackableException', "Unknown Modifier: '$modifier'");
    }

    $foo->change($property . ':' . $modifier, $diff);
    $this->assertEquals($new, $foo->get($property));
  }

  /**
   * Data Provider for properties
   *
   * @return array
   *   Use Cases
   */
  public function modifierProvider() {
    $cases = array();

    // Fields
    $cases[] = array('a', 0, 10, 10, '+');
    $cases[] = array('a', 20, 5, 25, '+');
    $cases[] = array('a', null, 5, 5, '+');
    $cases[] = array('a', 20, -5, 15, '+');

    $cases[] = array('a', 15, 5, 10, '-');
    $cases[] = array('a', 5, 10, -5, '-');
    $cases[] = array('a', 20, 19, 1, '-');
    $cases[] = array('a', 20, -5, 25, '-');

    $cases[] = array('a', 30, 30, 30, '=');

    // Invalid Modifier
    $cases[] = array('a', 10, 10, 10, '*', true);
    $cases[] = array('a', 10, 10, 10, '&', true);
    $cases[] = array('a', 10, 10, 10, '^', true);
    $cases[] = array('a', 10, 10, 10, '$', true);

    return $cases;
  }

  /**
   * Data Provider for properties
   *
   * @return array
   *   Use Cases
   */
  public function shiftProvider() {
    $cases = array();

    // Fields
    $cases[] = array('a', 0, 50);
    $cases[] = array('a', 22, 10);
    $cases[] = array('a', 100, 52);
    $cases[] = array('a', null, 12);
    $cases[] = array('a', 'test', 19, true);

    // Other
    $cases[] = array('z', 99, 42);
    $cases[] = array('z', null, 42);
    $cases[] = array('z', 0, 42);

    return $cases;
  }

  /**
   * Data Provider for properties
   *
   * @return array
   *   Use Cases
   */
  public function propertyProvider() {
    $cases = array();

    // String
    $cases[] = array('a', 'b', 'c');
    // Numeric
    $cases[] = array('b', 1, 2);
    // Bool
    $cases[] = array('c', 1, 0);
    // Bool
    $cases[] = array('c', false, true);
    // Objects
    $cases[] = array('d', (object) array(1,2,3), (object) array(2,3,4));
    // Arrays
    $cases[] = array('e', array(1,2,3), array(2,3,4));

    return $cases;
  }

  /**
   * Data Provider for others
   *
   * @return array
   *   Use Cases
   */
  public function otherProvider() {
    $cases = array();

    // Others
    $cases[] = array('z', 'b', 'c');
    $cases[] = array('z', (object) array(1,2,3), 'abc');

    return $cases;
  }

  /**
   * Data Provider for others
   *
   * @return array
   *   Use Cases
   */
  public function allProvider() {
    return array_merge($this->propertyProvider(), $this->otherProvider());
  }
}

class TrackableMock extends Trackable {
  public static $fields = array('a','b','c','d','e','f');

  public function isProperty($property) {
    return in_array($property, self::$fields);
  }

  public function clean() {
    return $this->_migrateDirtyToClean();
  }

  public function exposeValueDirty($property) {
    return $this->_dirty[$property];
  }

  public function exposeValueClean($property) {
    return $this->_clean[$property];
  }

  public function exposeValueOther($property) {
    return $this->_others[$property];
  }
}