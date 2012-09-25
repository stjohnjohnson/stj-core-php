<?php

namespace STJ\Database\Dbo;

use ReflectionMethod,
    PDO;

use STJ\Core\Conf;

/**
 * Validated Test
 */
class ValidatedTest extends \UnitTest {
  public static $pdo;

  /**
   * Initialize PDO object and table
   */
  public static function setUpBeforeClass() {
    Conf::init('conf/settings.ini');
    self::$pdo = new PDO(Conf::database('dsn'), Conf::database('username'), Conf::database('password'));
    self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    self::$pdo->setAttribute(PDO::ATTR_FETCH_TABLE_NAMES, 1);

    // Create tablesh
    self::$pdo->exec('DROP TABLE IF EXISTS FOOBARVALIDATED');
    self::$pdo->exec("CREATE TABLE FOOBARVALIDATED (
          example_id  INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          title       VARCHAR(15) NOT NULL,
          cost        FLOAT(2) NOT NULL DEFAULT '0.0',
          counter     INT(10) UNSIGNED NOT NULL,
          status      ENUM('Open', 'Closed') NOT NULL DEFAULT 'OPEN',
          animal      SET('Cat', 'Dog', 'Fish') NOT NULL DEFAULT '',
          is_happy    BOOL NOT NULL DEFAULT 0,
          m_time      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (example_id),
          UNIQUE KEY (counter)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

    // Setup connectors
    Validated::setDbRW(self::$pdo);
    Validated::setDbRO(self::$pdo);
  }

  /**
   * Delete the tables after all tests
   */
  public static function tearDownAfterClass() {
    self::$pdo->exec('DROP TABLE IF EXISTS FOOBARVALIDATED');
  }

  /**
   * Empty tables after each test
   */
  public function tearDown() {
    self::$pdo->exec('TRUNCATE FOOBARVALIDATED');
  }

  /**
   * @test
   * @group Validated
   * @group Validated.validate
   * @dataProvider providerValidate
   * @covers STJ\Database\Dbo\Validated::validate
   * @covers STJ\Database\Dbo\Validated::_checkRequiredProperties
   * @covers STJ\Database\Dbo\Validated::_getRequiredProperties
   */
  public function validate(array $properties, array $validations = null, $error = null) {
    $foo = new FooBarValidated();
    $foo->setFromArray($properties);

    if ($error) {
      $this->setExpectedException('STJ\\Database\\Dbo\\ValidationException', $error);
    }

    // Validate
    $foo->validate($validations);
    // Pass Go
    $this->assertTrue(true);
  }

  /**
   * Provider for validate
   *
   * @return array
   */
  public function providerValidate() {
    $array = array();

    // Normal
    $array[] = array(array('example_id' => 15, 'title' => 'sample'));

    // Invalid Int + status
    $array[] = array(array('example_id' => -15, 'status' => 'Lost'), null, "status is an Invalid Option\nexample_id should be Positive");

    // Ovverride validation methods
    $array[] = array(array('example_id' => -15, 'status' => 'Lost'), array());

    return $array;
  }

  /**
   * @test
   * @group Validated
   * @group Validated.checkDataTypes
   * @dataProvider providerCheckDataTypes
   * @covers STJ\Database\Dbo\Validated::_checkDataTypes
   */
  public function checkDataTypes($property, $value, $result = array()) {
    $foo = new FooBarValidated();
    $method = new ReflectionMethod($foo, '_checkDataTypes');
    $method->setAccessible(true);

    $foo->$property = $value;

    $errors = $method->invoke($foo);
    $this->assertEquals($result, $errors->toArray());
  }

  /**
   * Provider for checkDataTypes
   *
   * @return array
   */
  public function providerCheckDataTypes() {
    $array = array();

    $array[] = array('example_id', 15);
    $array[] = array('example_id', 'abc', array('example_id' => array('example_id should be an Integer')));
    $array[] = array('example_id', -15, array('example_id' => array('example_id should be Positive')));

    $array[] = array('title', 'test');
    $array[] = array('title', '1234567890123456', array('title' => array('title must be within 15 characters')));

    $array[] = array('cost', 25.22);
    $array[] = array('cost', 'asdfasdf', array('cost' => array('cost should be a Float')));

    $array[] = array('status', 'Open');
    $array[] = array('status', 'Blah', array('status' => array('status is an Invalid Option')));

    $array[] = array('animal', 'Cat');
    $array[] = array('animal', array('Cat','Dog'));
    $array[] = array('animal', array('Plankton'), array('animal' => array('animal contains an Invalid Option')));

    $array[] = array('is_happy', false);
    $array[] = array('is_happy', true);
    $array[] = array('is_happy', 3.14, array('is_happy' => array('is_happy should be Boolean')));

    return $array;
  }

  /**
   * @test
   * @group Validated
   * @group Validated.validateProperty
   * @dataProvider providerValidateProperty
   * @covers STJ\Database\Dbo\Validated::validateProperty
   * @covers STJ\Database\Dbo\Validated::validateProperties
   */
  public function validateProperty($property, $value, \Closure $closure, $result = array()) {
    $foo = new FooBarValidated();
    $foo->$property = $value;

    $errors = $foo->validateProperty($property, $closure, 'Sample %s');
    $this->assertEquals($result, $errors->toArray());
  }

  /**
   * Provider for checkDataTypes
   *
   * @return array
   */
  public function providerValidateProperty() {
    $array = array();

    $array[] = array('example_id', 15, Closures::isInteger());
    $array[] = array('example_id', 15, Closures::isBoolean(), array('example_id' => array('Sample example_id')));

    return $array;
  }
}

class ValidatedMock extends Validated {
  public static function leverage($method, array $args = array()) {
    return call_user_func_array('static::' . $method, $args);
  }

  public function clean() {
    return $this->_migrateDirtyToClean();
  }
}

class FooBarValidated extends ValidatedMock {
  protected function _checkSomethingElse() {
    return array();
  }

  protected function dontRunThis() {
    throw new \Exception('Eek!');
  }
}