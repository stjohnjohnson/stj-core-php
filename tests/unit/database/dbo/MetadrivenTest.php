<?php

namespace STJ\Database\Dbo;

use ReflectionMethod,
    PDO;

use STJ\Core\Conf;

/**
 * Metadriven Test
 */
class MetadrivenTest extends \UnitTest {
  public static $pdo;

  /**
   * Initialize PDO object and table
   */
  public static function setUpBeforeClass() {
    Conf::init('conf/settings.ini');
    self::$pdo = new PDO(Conf::database('dsn'), Conf::database('username'), Conf::database('password'));
    self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    self::$pdo->setAttribute(PDO::ATTR_FETCH_TABLE_NAMES, 1);

    // Create tables
    self::$pdo->exec('DROP TABLE IF EXISTS FOOBARMETA');
    self::$pdo->exec("CREATE TABLE FOOBARMETA (
          example_id  INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          title       VARCHAR(255) NOT NULL,
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
    MetaDriven::setDbRW(self::$pdo);
    MetaDriven::setDbRO(self::$pdo);
  }

  /**
   * Delete the table after all tests
   */
  public static function tearDownAfterClass() {
    self::$pdo->exec('DROP TABLE IF EXISTS FOOBARMETA');
  }

  /**
   * Empty Foobarmodel table after each test
   */
  public function tearDown() {
    self::$pdo->exec('TRUNCATE FOOBARMETA');
  }

  /**
   * Data Provider for class data
   *
   * @return array
   */
  public function classProvider() {
    $cases = array();

    $cases[] = array('STJ\\Database\\Dbo\\FooBarMetaFake');

    return $cases;
  }

  /**
   * @test
   * @group Metadriven
   * @group Metadriven.removeNamespace
   * @covers STJ\Database\Dbo\Metadriven
   */
  public function removeNamespace() {
    $this->assertEquals('Welcome', MetadrivenMock::leverage('_removeNamespace', array('STJ\\Something\\Welcome')));
    $this->assertEquals('Welcome', MetadrivenMock::leverage('_removeNamespace', array('Welcome')));
  }

  /**
   * @test
   * @group Metadriven
   * @group Metadriven.metaClass
   * @covers STJ\Database\Dbo\Metadriven
   * @dataProvider classProvider
   */
  public function metaClass($class) {
    $meta = MetadrivenMock::leverage('_getMetaData', array($class));
    $class = MetadrivenMock::leverage('_removeNamespace', array($class));
    $this->assertEquals($class, MetadrivenMock::leverage('_getMetaClass', array($meta['table'])));
    $this->assertEquals(false, MetadrivenMock::leverage('_getMetaClass', array('garbage')));
  }

  /**
   * @test
   * @group Metadriven
   * @group Metadriven.metaTable
   * @covers STJ\Database\Dbo\Metadriven
   * @dataProvider classProvider
   */
  public function metaTable($class) {
    $this->assertEquals(MetadrivenMock::$classes[$class]['table'], MetadrivenMock::leverage('_getMetaTable', array($class)));
  }

  /**
   * @test
   * @group Metadriven
   * @group Metadriven.metaFields
   * @covers STJ\Database\Dbo\Metadriven
   * @dataProvider classProvider
   */
  public function metaFields($class) {
    $this->assertEquals(MetadrivenMock::$classes[$class]['fields'], MetadrivenMock::leverage('_getMetaFields', array($class)));
  }

  /**
   * @test
   * @group Metadriven
   * @group Metadriven.metaKeys
   * @covers STJ\Database\Dbo\Metadriven
   * @dataProvider classProvider
   */
  public function metaKeys($class) {
    $this->assertEquals(MetadrivenMock::$classes[$class]['keys'], MetadrivenMock::leverage('_getMetaKeys', array($class)));
    $this->assertEquals(reset(MetadrivenMock::$classes[$class]['keys']), MetadrivenMock::leverage('_getMetaKeys', array($class, true)));
  }

  /**
   * @test
   * @group Metadriven
   * @group Metadriven.metaAuto
   * @covers STJ\Database\Dbo\Metadriven
   * @dataProvider classProvider
   */
  public function metaAuto($class) {
    $this->assertEquals(MetadrivenMock::$classes[$class]['auto'], MetadrivenMock::leverage('_getMetaAuto', array($class)));
  }

  /**
   * @test
   * @group Metadriven
   * @group Metadriven.properties
   * @covers STJ\Database\Dbo\Metadriven::isProperty
   * @covers STJ\Database\Dbo\Metadriven::getProperties
   * @covers STJ\Database\Dbo\Metadriven::_getPropertyType
   * @covers STJ\Database\Dbo\Metadriven::_getPropertyOptions
   * @dataProvider propertyProvider
   */
  public function properties($property, $is, $type, $options = array()) {
    $foo = new FooBarMetaFake();

    $this->assertEquals($is, $foo->isProperty($property));
    $this->assertEquals($type, FooBarMetaFake::leverage('_getPropertyType', array($property, 'STJ\\Database\\Dbo\\FooBarMetaFake')));
    $this->assertEquals($options, FooBarMetaFake::leverage('_getPropertyOptions', array($property, 'STJ\\Database\\Dbo\\FooBarMetaFake')));
    $this->assertEquals($is, in_array($property, $foo->getProperties()));
  }

  /**
   * Data Provider for properties test
   *
   * @return array
   */
  public function propertyProvider() {
    $cases = array();

    // Real
    $cases[] = array('foo_id', true, 'integer');
    $cases[] = array('title', true, 'varchar(200)');
    $cases[] = array('is_active', true, 'tinyint(1)');
    $cases[] = array('fav_food', true, "enum('apple','banana','orange')", array('apple','banana','orange'));
    $cases[] = array('mood', true, "set('happy','sad','angry')", array('happy','sad','angry'));

    // False
    $cases[] = array('banana', false, false);

    return $cases;
  }

  /**
   * @test
   * @group Metadriven
   * @group Metadriven.convertType
   * @covers STJ\Database\Dbo\Metadriven
   * @dataProvider convertTypeProvider
   */
  public function convertType($type, $mysql, $php) {
    $output = MetadrivenMock::leverage('_convertFromDBFormat', array($type, $mysql));
    $this->assertEquals($php, $output);

    $output = MetadrivenMock::leverage('_convertToDBFormat', array($type, $php));
    $this->assertEquals($mysql, $output);
  }

  /**
   * Data Provider for convertType test
   *
   * @return array
   */
  public function convertTypeProvider() {
    $cases = array();

    $cases[] = array('integer', null, null);
    $cases[] = array('integer', '50', 50);
    $cases[] = array('integer', '5000', 5000);
    $cases[] = array('float', '42.22', 42.22);
    $cases[] = array('timestamp', '0000-00-00 00:00:00', 0);
    $cases[] = array('timestamp', '2012-12-21 15:10:22', 1356102622);
    $cases[] = array('varchar(200)', 'Sample', 'Sample');
    $cases[] = array('tinyint(1)', '1', true);
    $cases[] = array('tinyint(1)', '0', false);
    $cases[] = array("enum('apple','banana','orange')", 'apple', 'apple');
    $cases[] = array("enum('apple','banana','orange')", 'banana', 'banana');
    $cases[] = array("set('happy','sad','angry')", 'happy', array('happy'));
    $cases[] = array("set('happy','sad','angry')", 'happy,angry', array('happy','angry'));

    return $cases;
  }

  /**
   * @test
   * @group Metadriven
   * @group Metadriven.getROPDOsError
   * @covers STJ\Database\Dbo\Metadriven
   * @expectedException STJ\Database\Dbo\MetadrivenException
   * @expectedExceptionMessage No Read-Only PDO Available
   */
  public function getROPDOsError() {
    MetadrivenMock::setDbRO(null);
    MetadrivenMock::getDbRO();
  }

  /**
   * @test
   * @group Metadriven
   * @group Metadriven.getRWPDOsError
   * @covers STJ\Database\Dbo\Metadriven
   * @expectedException STJ\Database\Dbo\MetadrivenException
   * @expectedExceptionMessage No Read-Write PDO Available
   */
  public function getRWPDOsError() {
    MetadrivenMock::setDbRW(null);
    MetadrivenMock::getDbRW();
  }

  /**
   * @test
   * @group Metadriven
   * @group Metadriven.getPDOs
   * @covers STJ\Database\Dbo\Metadriven
   */
  public function getPDOs() {
    MetadrivenMock::setDbRW('sampleRW');
    $this->assertEquals('sampleRW', MetadrivenMock::getDbRW());

    MetadrivenMock::setDbRO('sampleRO');
    $this->assertEquals('sampleRO', MetadrivenMock::getDbRO());
  }

  /**
   * @test
   * @group Metadriven
   * @group Metadriven.metaData
   * @covers STJ\Database\Dbo\Metadriven
   */
  public function metaData() {
    $expected = array (
      'fields' => array (
        'example_id' => array (
          'type' => 'int(10) unsigned',
          'null' => false,
          'default' => NULL,
        ),
        'title' => array (
          'type' => 'varchar(255)',
          'null' => false,
          'default' => NULL,
        ),
        'cost' => array (
          'type' => 'float',
          'null' => false,
          'default' => '0',
        ),
        'counter' => array (
          'type' => 'int(10) unsigned',
          'null' => false,
          'default' => NULL,
        ),
        'status' => array (
          'type' => 'enum(\'Open\',\'Closed\')',
          'null' => false,
          'default' => 'Open',
        ),
        'animal' => array (
          'type' => 'set(\'Cat\',\'Dog\',\'Fish\')',
          'null' => false,
          'default' => '',
        ),
        'is_happy' => array (
          'type' => 'tinyint(1)',
          'null' => false,
          'default' => '0',
        ),
        'm_time' => array (
          'type' => 'timestamp',
          'null' => false,
          'default' => 'CURRENT_TIMESTAMP',
        ),
      ),
      'keys' => array(
          array('example_id'),
          array('counter')
      ),
      'auto' => 'example_id',
      'table' => 'FOOBARMETA',
    );

    // Reset PDOs
    MetaDriven::setDbRO(self::$pdo);
    MetaDriven::setDbRW(self::$pdo);

    // Get output
    $output = FooBarMeta::leverage('_getMetaData', array('STJ\\Database\\Dbo\\FooBarMeta'));
    $this->assertEquals($expected, $output);

    // Get from internal cache
    $output = FooBarMeta::leverage('_getMetaData', array('STJ\\Database\\Dbo\\FooBarMeta'));
    $this->assertEquals($expected, $output);

    // Get from external cache
    FooBarMeta::reset();
    $output = FooBarMeta::leverage('_getMetaData', array('STJ\\Database\\Dbo\\FooBarMeta'));
    $this->assertEquals($expected, $output);
  }

  /**
   * @test
   * @group Metadriven
   * @group Metadriven.metaDataMissing
   * @covers STJ\Database\Dbo\Metadriven
   * @expectedException STJ\Database\Dbo\MetadrivenException
   * @expectedExceptionMessage Table Not Found: FooBarMissing
   */
  public function metaDataMissing() {
    // Reset PDOs
    MetaDriven::setDbRO(self::$pdo);
    MetaDriven::setDbRW(self::$pdo);

    // Get output
    $output = FooBarMeta::leverage('_getMetaData', array('STJ\\Database\\Dbo\\FooBarMissing'));
  }
}

class MetadrivenMock extends Metadriven {
  public static $classes = array(
      'STJ\\Database\\Dbo\\FooBarMetaFake' => array(
         'table' => 'foobar',
        'fields' => array(
            'foo_id' => array(
                'type' => 'integer',
                'null' => true,
             'default' => '0'
            ),
            'title' => array(
                'type' => 'varchar(200)',
                'null' => false,
             'default' => ''
            ),
            'is_active' => array(
                'type' => 'tinyint(1)',
                'null' => false,
             'default' => '1'
            ),
            'fav_food' => array(
                'type' => "enum('apple','banana','orange')",
                'null' => false,
             'default' => 'apple'
            ),
            'mood' => array(
                'type' => "set('happy','sad','angry')",
                'null' => false,
             'default' => 'happy'
            )
          ),
          'keys' => array(array('foo_id')),
          'auto' => 'foo_id'
      )
  );

  public static function leverage($method, array $args) {
    return call_user_func_array('static::' . $method, $args);
  }

  protected static function _getMetaData($class) {
    if (!isset(self::$classes[$class])) {
      throw new MetadrivenException('Table Not Found: ' . $class, 500);
    }
    // Store meta
    self::$_meta[self::_removeNamespace($class)] = self::$classes[$class];

    return self::$classes[$class];
  }
}

class FooBarMetaFake extends MetadrivenMock { }
class FooBarMeta extends Metadriven {
  public static function reset() {
    self::$_meta = array();
  }

  public static function leverage($method, array $args) {
    return call_user_func_array('static::' . $method, $args);
  }
}
