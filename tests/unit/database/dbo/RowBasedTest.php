<?php

namespace STJ\Database\Dbo;

use ReflectionMethod,
    PDO;

use STJ\Core\Conf;

/**
 * RowBased Test
 */
class RowBasedTest extends \UnitTest {
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
    self::$pdo->exec('DROP TABLE IF EXISTS FOOBARROWBASED');
    self::$pdo->exec("CREATE TABLE FOOBARROWBASED (
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

    self::$pdo->exec('DROP TABLE IF EXISTS foofailrowbased');
    self::$pdo->exec("CREATE TABLE foofailrowbased (
          title       VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

    // Belongs To
    self::$pdo->exec('DROP TABLE IF EXISTS fooBT');
    self::$pdo->exec("CREATE TABLE fooBT (
            fooBT_id  INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          counter     INT(10) NOT NULL DEFAULT 0,
           fooHA_id INT(10) UNSIGNED NOT NULL,
          PRIMARY KEY (fooBT_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

    // Has A
    self::$pdo->exec('DROP TABLE IF EXISTS fooHA');
    self::$pdo->exec("CREATE TABLE fooHA (
          fooHA_id  INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          cost        FLOAT(2) NOT NULL DEFAULT '0.0',
          PRIMARY KEY (fooHA_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

    // Has Many
    self::$pdo->exec('DROP TABLE IF EXISTS fooHM');
    self::$pdo->exec("CREATE TABLE fooHM (
       fooHM_id  INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          title       VARCHAR(255) NOT NULL,
          PRIMARY KEY (fooHM_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

    // Has Many Helper
    self::$pdo->exec('DROP TABLE IF EXISTS fooHMH');
    self::$pdo->exec("CREATE TABLE fooHMH (
 fooHMH_id  INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          title       VARCHAR(255) NOT NULL,
       fooHM_id  INT(10) UNSIGNED NOT NULL,
          PRIMARY KEY (fooHMH_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

    // Has Many Through Begining
    self::$pdo->exec('DROP TABLE IF EXISTS fooHMTB');
    self::$pdo->exec("CREATE TABLE fooHMTB (
fooHMTB_id  INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          title       VARCHAR(255) NOT NULL,
          PRIMARY KEY (fooHMTB_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

    // Has Many Through - Middle
    self::$pdo->exec('DROP TABLE IF EXISTS fooHMTM');
    self::$pdo->exec("CREATE TABLE fooHMTM (
fooHMTB_id  INT(10) UNSIGNED NOT NULL,
fooHMTE_id  INT(10) UNSIGNED NOT NULL,
          title       VARCHAR(255) NOT NULL,
          PRIMARY KEY (fooHMTB_id,fooHMTE_id,title)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

    // Has Many Through End
    self::$pdo->exec('DROP TABLE IF EXISTS fooHMTE');
    self::$pdo->exec("CREATE TABLE fooHMTE (
fooHMTE_id  INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          title       VARCHAR(255) NOT NULL,
          PRIMARY KEY (fooHMTE_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");

    // Setup connectors
    RowBased::setDbRW(self::$pdo);
    RowBased::setDbRO(self::$pdo);
  }

  /**
   * Delete the tables after all tests
   */
  public static function tearDownAfterClass() {
    self::$pdo->exec('DROP TABLE IF EXISTS FOOBARROWBASED');
    self::$pdo->exec('DROP TABLE IF EXISTS foofailrowbased');
    self::$pdo->exec('DROP TABLE IF EXISTS fooBT');
    self::$pdo->exec('DROP TABLE IF EXISTS fooHA');
    self::$pdo->exec('DROP TABLE IF EXISTS fooHM');
    self::$pdo->exec('DROP TABLE IF EXISTS fooHMH');
    self::$pdo->exec('DROP TABLE IF EXISTS fooHMTB');
    self::$pdo->exec('DROP TABLE IF EXISTS fooHMTM');
    self::$pdo->exec('DROP TABLE IF EXISTS fooHMTE');
  }

  /**
   * Empty tables after each test
   */
  public function tearDown() {
    self::$pdo->exec('TRUNCATE FOOBARROWBASED');
    self::$pdo->exec('TRUNCATE foofailrowbased');
    self::$pdo->exec('TRUNCATE fooBT');
    self::$pdo->exec('TRUNCATE fooHA');
    self::$pdo->exec('TRUNCATE fooHM');
    self::$pdo->exec('TRUNCATE fooHMH');
    self::$pdo->exec('TRUNCATE fooHMTB');
    self::$pdo->exec('TRUNCATE fooHMTM');
    self::$pdo->exec('TRUNCATE fooHMTE');
  }

  /**
   * Data Provider for selectStatement test
   *
   * @return array
   */
  public function providerSelects() {
    $cases = array();

    // Basic
    $cases[] = array(__NAMESPACE__ . '\\FooBarRowBased', 'SELECT `FOOBARROWBASED`.`example_id`, `FOOBARROWBASED`.`title`, `FOOBARROWBASED`.`cost`, ' .
           '`FOOBARROWBASED`.`counter`, `FOOBARROWBASED`.`status`, `FOOBARROWBASED`.`animal`, `FOOBARROWBASED`.`is_happy`, ' .
           '`FOOBARROWBASED`.`m_time` FROM `FOOBARROWBASED`');

    // Has A
    $cases[] = array(__NAMESPACE__ . '\\fooHA', 'SELECT `fooHA`.`fooHA_id`, `fooHA`.`cost`, '
        . '`fooBT`.`fooBT_id`, `fooBT`.`counter`, `fooBT`.`fooHA_id` '
        . 'FROM `fooHA` LEFT JOIN `fooBT` USING (`fooHA_id`)');
    // Belongs To
    $cases[] = array(__NAMESPACE__ . '\\fooBT', 'SELECT `fooBT`.`fooBT_id`, '
        . '`fooBT`.`counter`, `fooBT`.`fooHA_id`, `fooHA`.`fooHA_id`, '
        . '`fooHA`.`cost` FROM `fooBT` LEFT JOIN `fooHA` USING (`fooHA_id`)');
    $cases[] = array(__NAMESPACE__ . '\\fooHMH', 'SELECT `fooHMH`.`fooHMH_id`, '
        . '`fooHMH`.`title`, `fooHMH`.`fooHM_id`, `fooHM`.`fooHM_id`, '
        . '`fooHM`.`title` FROM `fooHMH` LEFT JOIN `fooHM` USING (`fooHM_id`)');
    $cases[] = array(__NAMESPACE__ . '\\fooHMTM', 'SELECT `fooHMTM`.'
        . '`fooHMTB_id`, `fooHMTM`.`fooHMTE_id`, '
        . '`fooHMTM`.`title`, `fooHMTB`.`fooHMTB_id`, '
        . '`fooHMTB`.`title`, `fooHMTE`.`fooHMTE_id`, '
        . '`fooHMTE`.`title` FROM `fooHMTM` LEFT JOIN `fooHMTB` '
        . 'USING (`fooHMTB_id`) LEFT JOIN `fooHMTE` USING (`fooHMTE_id`)');
    // Has Many
    $cases[] = array(__NAMESPACE__ . '\\fooHM', 'SELECT `fooHM`.`fooHM_id`, '
        . '`fooHM`.`title`, `fooHMH`.`fooHMH_id`, `fooHMH`.`title`, '
        . '`fooHMH`.`fooHM_id` FROM `fooHM` LEFT JOIN `fooHMH` '
        . 'USING (`fooHM_id`)');
    // Has Many Through
    $cases[] = array(__NAMESPACE__ . '\\fooHMTB', 'SELECT `fooHMTB`.'
        . '`fooHMTB_id`, `fooHMTB`.`title`, `fooHMTE`.'
        . '`fooHMTE_id`, `fooHMTE`.`title`, `fooHMTM`.'
        . '`fooHMTB_id`, `fooHMTM`.`fooHMTE_id`, '
        . '`fooHMTM`.`title` FROM `fooHMTB` LEFT JOIN '
        . '`fooHMTM` USING (`fooHMTB_id`) LEFT JOIN '
        . '`fooHMTE` USING (`fooHMTE_id`)');
    $cases[] = array(__NAMESPACE__ . '\\fooHMTE', 'SELECT `fooHMTE`.'
        . '`fooHMTE_id`, `fooHMTE`.`title`, `fooHMTB`.'
        . '`fooHMTB_id`, `fooHMTB`.`title`, `fooHMTM`.'
        . '`fooHMTB_id`, `fooHMTM`.`fooHMTE_id`, '
        . '`fooHMTM`.`title` FROM `fooHMTE` LEFT JOIN '
        . '`fooHMTM` USING (`fooHMTE_id`) LEFT JOIN '
        . '`fooHMTB` USING (`fooHMTB_id`)');

    return $cases;
  }

  /**
   * @test
   * @group RowBased
   * @group RowBased.selectStatement
   * @covers STJ\Database\Dbo\RowBased::_getSQLSelect
   * @dataProvider providerSelects
   */
  public function selectStatement($class, $expected) {
    $sql = $class::leverage('_getSQLSelect');
    $this->assertEquals($expected, $sql);
  }

  /**
   * @test
   * @group RowBased
   * @group RowBased.insertStatement
   * @covers STJ\Database\Dbo\RowBased::_getSQLInsert
   */
  public function insertStatement() {
    $foo = new FooBarRowBased();
    $method = new ReflectionMethod($foo, '_getSQLInsert');
    $method->setAccessible(true);

    // Add some fields
    $foo->title = 'test';
    $foo->cost = 52.2;
    $foo->counter = 40;
    $foo->status = 'Open';
    $foo->animal = array('Cat','Fish');
    $foo->is_happy = false;

    $params = array();
    $sql = $method->invokeArgs($foo, array(&$params));

    $this->assertEquals('INSERT INTO `FOOBARROWBASED` (`example_id`, `title`, `cost`, `counter`, `status`, `animal`, `is_happy`, `m_time`) VALUES (DEFAULT,:title,:cost,:counter,:status,:animal,:is_happy,DEFAULT)', $sql);
    $this->assertEquals(array(
        'title' => 'test',
         'cost' => '52.2',
      'counter' => '40',
       'status' => 'Open',
       'animal' => 'Cat,Fish',
     'is_happy' => 0), $params);
  }

  /**
   * @test
   * @group RowBased
   * @group RowBased.updateStatement
   * @covers STJ\Database\Dbo\RowBased::_getSQLUpdate
   */
  public function updateStatement() {
    $foo = new FooBarRowBased();
    $method = new ReflectionMethod($foo, '_getSQLUpdate');
    $method->setAccessible(true);

    // Add some fields
    $foo->example_id = 10;
    $foo->title = 'test';
    $foo->cost = 52.2;
    $foo->counter = 40;
    $foo->status = 'Open';
    $foo->animal = array('Cat','Fish');
    $foo->is_happy = false;

    // Mark them as clean
    $foo->clean();

    // Change some things
    $foo->title = 'test new';
    $foo->add('cost', 14.50);
    $foo->sub('counter', 5);

    // Invoke
    $params = array();
    $sql = $method->invokeArgs($foo, array(&$params));

    $this->assertEquals('UPDATE `FOOBARROWBASED` SET `title` = ?, `cost` = `cost` + ?, `counter` = `counter` - ?', $sql);
    $this->assertEquals(array('test new','14.5','5'), $params);
  }

  /**
   * @test
   * @group RowBased
   * @group RowBased.whereStatement
   * @covers STJ\Database\Dbo\RowBased::_getSQLWhereIn
   * @dataProvider whereProvider
   */
  public function whereStatement($field, $value, $expectedSQL, $expectedParams, $exception = false) {
    $foo = new FooBarRowBased();

    // Set exceptions
    if ($exception) {
      $this->setExpectedException('STJ\\Database\\Dbo\\RowBasedException', $exception);
    }

    $params = array();
    $sql = FooBarRowBased::leverage('_getSQLWhereIn', array(array($field => $value), &$params));

    $this->assertEquals($expectedSQL, $sql);
    $this->assertEquals($expectedParams, $params);
  }

  /**
   * Data Provider for whereStatement test
   *
   * @return array
   */
  public function whereProvider() {
    $cases = array();

    // Normal case - 1 value
    $cases[] = array('title', 'test', '`FOOBARROWBASED`.`title` = ?', array('test'));
    // Normal case - 2 values
    $cases[] = array('title', array('foo','bar'), '`FOOBARROWBASED`.`title` IN (?,?)', array('foo','bar'));
    // Normal case - 0 values
    $cases[] = array('title', array(), '', array());
    // Normal case - null value
    $cases[] = array('title', null, '`FOOBARROWBASED`.`title` IS NULL', array());
    // Inverse case - null value
    $cases[] = array('title:neq', null, '`FOOBARROWBASED`.`title` IS NOT NULL', array());


    // Comparisons
    $cases[] = array('title:eq', 'foo', '`FOOBARROWBASED`.`title` = ?', array('foo'));
    $cases[] = array('title:=', 'foo', '`FOOBARROWBASED`.`title` = ?', array('foo'));
    $cases[] = array('title:neq', 'foo', '`FOOBARROWBASED`.`title` != ?', array('foo'));
    $cases[] = array('title:!=', 'foo', '`FOOBARROWBASED`.`title` != ?', array('foo'));
    $cases[] = array('title:lt', 'foo', '`FOOBARROWBASED`.`title` < ?', array('foo'));
    $cases[] = array('title:lte', 'foo', '`FOOBARROWBASED`.`title` <= ?', array('foo'));
    $cases[] = array('title:<', 'foo', '`FOOBARROWBASED`.`title` < ?', array('foo'));
    $cases[] = array('title:<=', 'foo', '`FOOBARROWBASED`.`title` <= ?', array('foo'));
    $cases[] = array('title:gt', 'foo', '`FOOBARROWBASED`.`title` > ?', array('foo'));
    $cases[] = array('title:gte', 'foo', '`FOOBARROWBASED`.`title` >= ?', array('foo'));
    $cases[] = array('title:>', 'foo', '`FOOBARROWBASED`.`title` > ?', array('foo'));
    $cases[] = array('title:>=', 'foo', '`FOOBARROWBASED`.`title` >= ?', array('foo'));
    $cases[] = array('title:like', 'foo', '`FOOBARROWBASED`.`title` LIKE ?', array('foo'));
    $cases[] = array('title:not like', 'foo', '`FOOBARROWBASED`.`title` NOT LIKE ?', array('foo'));
    $cases[] = array('title:lt', array('foo','bar'), '', array(), "Operator '<' supports only one argument");

    // Many
    $cases[] = array('title:in', array('foo','bar'), '`FOOBARROWBASED`.`title` IN (?,?)', array('foo','bar'));
    $cases[] = array('title:not in', array('foo','bar'), '`FOOBARROWBASED`.`title` NOT IN (?,?)', array('foo','bar'));

    // Between
    $cases[] = array('title:between', array('foo','bar'), '`FOOBARROWBASED`.`title` BETWEEN ? AND ?', array('foo','bar'));
    $cases[] = array('title:not between', array('foo','bar'), '`FOOBARROWBASED`.`title` NOT BETWEEN ? AND ?', array('foo','bar'));
    $cases[] = array('title:between', array('foo'), '', array(), "Operator 'between' requires two arguments");

    // Invalid
    $cases[] = array('title:barf', array('foo'), '', array(), "Unknown Operator 'barf'");

    return $cases;
  }

  /**
   * @test
   * @group RowBased
   * @group RowBased.loadSuccess
   * @covers STJ\Database\Dbo\RowBased::_performLoad
   */
  public function loadSuccess() {
    $foo = new FooBarRowBased();
    $method = new ReflectionMethod($foo, '_performLoad');
    $method->setAccessible(true);

    // Create sample value
    $title = 'test: ' . __METHOD__;
    $stmt = self::$pdo->prepare("INSERT INTO FOOBARROWBASED (`title`) VALUES (?)");
    $stmt->execute(array($title));

    // Get saved ID
    $foo->example_id = self::$pdo->lastInsertId();
    $method->invoke($foo, false);

    // Make sure title loaded
    $this->assertEquals($title, $foo->title);

    // Reset and try with RW
    $foo->title = null;
    $method->invoke($foo, true);

    // Make sure title loaded
    $this->assertEquals($title, $foo->title);
  }

  public function providerProcessLoad() {
    $cases = array();

    // Empty Results
    $cases[] = array(__NAMESPACE__ . '\\FooBarRowBased', array(), array());

    // Normal Row
    $cases[] = array(__NAMESPACE__ . '\\FooBarRowBased', array(), array());

    // Has A
    $cases[] = array(__NAMESPACE__ . '\\fooHA', array(
      0 => array(
        'fooHA.fooHA_id' => '1',
        'fooHA.cost' => '0.71',
        'fooBT.fooBT_id' => '1',
        'fooBT.counter' => '6',
        'fooBT.fooHA_id' => '1',
      ),
      1 => array(
        'fooHA.fooHA_id' => '2',
        'fooHA.cost' => '0.17',
        'fooBT.fooBT_id' => '2',
        'fooBT.counter' => '3',
        'fooBT.fooHA_id' => '2',
      ),
      2 => array(
        'fooHA.fooHA_id' => '3',
        'fooHA.cost' => '0.23',
        'fooBT.fooBT_id' => NULL,
        'fooBT.counter' => NULL,
        'fooBT.fooHA_id' => NULL,
      ),
    ), array(
      1 => array(
        'fooHA_id' => 1,
        'cost' => 0.71,
        'fooBT' =>   (object) array(
          'fooBT_id' => 1,
          'counter' => 6,
          'fooHA_id' => 1,
        ),
      ),
      2 => array(
        'fooHA_id' => 2,
        'cost' => 0.17,
        'fooBT' =>   (object) array(
          'fooBT_id' => 2,
          'counter' => 3,
          'fooHA_id' => 2,
        ),
      ),
      3 => array(
        'fooHA_id' => 3,
        'cost' => 0.23,
      ),
    ), array('fooHA','fooBT'));

    // Belongs To
    $cases[] = array(__NAMESPACE__ . '\\fooBT', array(
      0 => array(
        'fooBT.fooBT_id' => '1',
        'fooBT.counter' => '10',
        'fooBT.fooHA_id' => '1',
        'fooHA.fooHA_id' => '1',
        'fooHA.cost' => '1.32',
      ),
      1 => array(
        'fooBT.fooBT_id' => '2',
        'fooBT.counter' => '0',
        'fooBT.fooHA_id' => '2',
        'fooHA.fooHA_id' => '2',
        'fooHA.cost' => '1.19',
      ),
    ), array(
      1 => array(
        'fooBT_id' => 1,
        'counter' => 10,
        'fooHA_id' => 1,
        'fooHA' =>   (object) array(
          'fooHA_id' => 1,
          'cost' => 1.32,
        ),
      ),
      2 => array(
        'fooBT_id' => 2,
        'counter' => 0,
        'fooHA_id' => 2,
        'fooHA' =>   (object) array(
          'fooHA_id' => 2,
          'cost' => 1.19,
        ),
      ),
    ), array('fooHA','fooBT'));

    // Has Many Through
    $cases[] = array(__NAMESPACE__ . '\\fooHMTB', array(
      0 => array (
        'fooHMTB.fooHMTB_id' => '1',
        'fooHMTB.title' => 'begin_1',
        'fooHMTE.fooHMTE_id' => '1',
        'fooHMTE.title' => 'end_1',
        'fooHMTM.fooHMTB_id' => '1',
        'fooHMTM.fooHMTE_id' => '1',
        'fooHMTM.title' => 'middle_10',
      ),
      1 => array (
        'fooHMTB.fooHMTB_id' => '1',
        'fooHMTB.title' => 'begin_1',
        'fooHMTE.fooHMTE_id' => '2',
        'fooHMTE.title' => 'end_2',
        'fooHMTM.fooHMTB_id' => '1',
        'fooHMTM.fooHMTE_id' => '2',
        'fooHMTM.title' => 'middle_6',
      ),
      2 => array (
        'fooHMTB.fooHMTB_id' => '1',
        'fooHMTB.title' => 'begin_1',
        'fooHMTE.fooHMTE_id' => '3',
        'fooHMTE.title' => 'end_3',
        'fooHMTM.fooHMTB_id' => '1',
        'fooHMTM.fooHMTE_id' => '3',
        'fooHMTM.title' => 'middle_2',
      ),
      3 => array (
        'fooHMTB.fooHMTB_id' => '1',
        'fooHMTB.title' => 'begin_1',
        'fooHMTE.fooHMTE_id' => '4',
        'fooHMTE.title' => 'end_4',
        'fooHMTM.fooHMTB_id' => '1',
        'fooHMTM.fooHMTE_id' => '4',
        'fooHMTM.title' => 'middle_8',
      ),
      4 => array (
        'fooHMTB.fooHMTB_id' => '1',
        'fooHMTB.title' => 'begin_1',
        'fooHMTE.fooHMTE_id' => '5',
        'fooHMTE.title' => 'end_5',
        'fooHMTM.fooHMTB_id' => '1',
        'fooHMTM.fooHMTE_id' => '5',
        'fooHMTM.title' => 'middle_4',
      ),
      5 => array (
        'fooHMTB.fooHMTB_id' => '2',
        'fooHMTB.title' => 'begin_2',
        'fooHMTE.fooHMTE_id' => '1',
        'fooHMTE.title' => 'end_1',
        'fooHMTM.fooHMTB_id' => '2',
        'fooHMTM.fooHMTE_id' => '1',
        'fooHMTM.title' => 'middle_5',
      ),
      6 => array (
        'fooHMTB.fooHMTB_id' => '2',
        'fooHMTB.title' => 'begin_2',
        'fooHMTE.fooHMTE_id' => '2',
        'fooHMTE.title' => 'end_2',
        'fooHMTM.fooHMTB_id' => '2',
        'fooHMTM.fooHMTE_id' => '2',
        'fooHMTM.title' => 'middle_1',
      ),
      7 => array (
        'fooHMTB.fooHMTB_id' => '2',
        'fooHMTB.title' => 'begin_2',
        'fooHMTE.fooHMTE_id' => '3',
        'fooHMTE.title' => 'end_3',
        'fooHMTM.fooHMTB_id' => '2',
        'fooHMTM.fooHMTE_id' => '3',
        'fooHMTM.title' => 'middle_7',
      ),
      8 => array (
        'fooHMTB.fooHMTB_id' => '2',
        'fooHMTB.title' => 'begin_2',
        'fooHMTE.fooHMTE_id' => '4',
        'fooHMTE.title' => 'end_4',
        'fooHMTM.fooHMTB_id' => '2',
        'fooHMTM.fooHMTE_id' => '4',
        'fooHMTM.title' => 'middle_3',
      ),
      9 => array (
        'fooHMTB.fooHMTB_id' => '2',
        'fooHMTB.title' => 'begin_2',
        'fooHMTE.fooHMTE_id' => '5',
        'fooHMTE.title' => 'end_5',
        'fooHMTM.fooHMTB_id' => '2',
        'fooHMTM.fooHMTE_id' => '5',
        'fooHMTM.title' => 'middle_9',
      ),
    ), array (
      1 => array (
      'fooHMTB_id' => 1,
        'title' => 'begin_1',
        'fooHMTEs' => array (
          1 => (object)array(
            'fooHMTE_id' => 1,
            'title' => 'end_1',
          ),
          2 => (object)array(
            'fooHMTE_id' => 2,
            'title' => 'end_2',
          ),
          3 => (object)array(
            'fooHMTE_id' => 3,
            'title' => 'end_3',
          ),
          4 => (object)array(
            'fooHMTE_id' => 4,
            'title' => 'end_4',
          ),
          5 => (object)array(
            'fooHMTE_id' => 5,
            'title' => 'end_5',
          ),
        ),
        'fooHMTMs' => array (
          '1:1:middle_10' => (object)array(
            'fooHMTB_id' => 1,
            'fooHMTE_id' => 1,
            'title' => 'middle_10',
            'fooHMTE' => (object)array(
              'fooHMTE_id' => 1,
              'title' => 'end_1',
            ),
          ),
          '1:2:middle_6' => (object)array(
            'fooHMTB_id' => 1,
            'fooHMTE_id' => 2,
            'title' => 'middle_6',
            'fooHMTE' => (object)array(
              'fooHMTE_id' => 2,
              'title' => 'end_2',
            ),
          ),
          '1:3:middle_2' => (object)array(
            'fooHMTB_id' => 1,
            'fooHMTE_id' => 3,
            'title' => 'middle_2',
            'fooHMTE' => (object)array(
              'fooHMTE_id' => 3,
              'title' => 'end_3',
            ),
          ),
          '1:4:middle_8' => (object)array(
            'fooHMTB_id' => 1,
            'fooHMTE_id' => 4,
            'title' => 'middle_8',
            'fooHMTE' => (object)array(
              'fooHMTE_id' => 4,
              'title' => 'end_4',
            ),
          ),
          '1:5:middle_4' => (object)array(
            'fooHMTB_id' => 1,
            'fooHMTE_id' => 5,
            'title' => 'middle_4',
            'fooHMTE' => (object)array(
              'fooHMTE_id' => 5,
              'title' => 'end_5',
            ),
          ),
        ),
      ),
      2 => array (
        'fooHMTB_id' => 2,
        'title' => 'begin_2',
        'fooHMTEs' =>
        array (
          1 => (object)array(
            'fooHMTE_id' => 1,
            'title' => 'end_1',
          ),
          2 => (object)array(
            'fooHMTE_id' => 2,
            'title' => 'end_2',
          ),
          3 => (object)array(
            'fooHMTE_id' => 3,
            'title' => 'end_3',
          ),
          4 => (object)array(
            'fooHMTE_id' => 4,
            'title' => 'end_4',
          ),
          5 => (object)array(
            'fooHMTE_id' => 5,
            'title' => 'end_5',
          ),
        ),
        'fooHMTMs' => array (
          '2:1:middle_5' => (object)array(
            'fooHMTB_id' => 2,
            'fooHMTE_id' => 1,
            'title' => 'middle_5',
            'fooHMTE' => (object)array(
              'fooHMTE_id' => 1,
              'title' => 'end_1',
            ),
          ),
          '2:2:middle_1' => (object)array(
            'fooHMTB_id' => 2,
            'fooHMTE_id' => 2,
            'title' => 'middle_1',
            'fooHMTE' => (object)array(
              'fooHMTE_id' => 2,
              'title' => 'end_2',
            ),
          ),
          '2:3:middle_7' => (object)array(
            'fooHMTB_id' => 2,
            'fooHMTE_id' => 3,
            'title' => 'middle_7',
            'fooHMTE' => (object)array(
              'fooHMTE_id' => 3,
              'title' => 'end_3',
            ),
          ),
          '2:4:middle_3' => (object)array(
            'fooHMTB_id' => 2,
            'fooHMTE_id' => 4,
            'title' => 'middle_3',
            'fooHMTE' => (object)array(
              'fooHMTE_id' => 4,
              'title' => 'end_4',
            ),
          ),
          '2:5:middle_9' => (object) array(
            'fooHMTB_id' => 2,
            'fooHMTE_id' => 5,
            'title' => 'middle_9',
            'fooHMTE' => (object)array(
              'fooHMTE_id' => 5,
              'title' => 'end_5',
            ),
          ),
        ),
      ),
    ), array('fooHMTB','fooHMTE','fooHMTM'));



    return $cases;
  }

  /**
   * @test
   * @group RowBased
   * @group RowBased.performLoadMagic
   * @covers STJ\Database\Dbo\RowBased::_processLoadResults
   * @covers STJ\Database\Dbo\RowBased::_rejoinHasManyThrough
   * @dataProvider providerProcessLoad
   */
  public function processLoadResults($class, $rows, $result, $preload = array()) {
    // Load the meta data
    foreach ($preload as $item) {
      $class::leverage('_getMetaTable', array($item));
    }
    // Check the output
    $this->assertEquals($result, $class::leverage('_processLoadResults', array($rows)));
  }

  /**
   * @test
   * @group RowBased
   * @group RowBased.loadFailure
   * @covers STJ\Database\Dbo\RowBased::_performLoad
   * @expectedException STJ\Database\Dbo\RowBasedException
   * @expectedExceptionMessage No Record(s) Found
   */
  public function loadFailure() {
    $foo = new FooBarRowBased();
    $method = new ReflectionMethod($foo, '_performLoad');
    $method->setAccessible(true);

    // Create sample value
    $title = 'test: ' . __METHOD__;
    $stmt = self::$pdo->prepare("INSERT INTO FOOBARROWBASED (`title`) VALUES (?)");
    $stmt->execute(array($title));

    // Get saved ID
    $foo->example_id = self::$pdo->lastInsertId() + 100;
    $method->invoke($foo, false);
  }

  /**
   * @test
   * @group RowBased
   * @group RowBased.loadFailureSafe
   * @covers STJ\Database\Dbo\RowBased::_performLoad
   */
  public function loadFailureSafe() {
    $foo = new FooBarRowBased();
    $method = new ReflectionMethod($foo, '_performLoad');
    $method->setAccessible(true);

    // Create sample value
    $title = 'test: ' . __METHOD__;
    $stmt = self::$pdo->prepare("INSERT INTO FOOBARROWBASED (`title`) VALUES (?)");
    $stmt->execute(array($title));

    // Get saved ID
    $foo->example_id = self::$pdo->lastInsertId() + 100;
    $method->invoke($foo, false, true);

    // Should not have loaded
    $this->assertTrue($foo->isNew());
  }

  /**
   * @test
   * @group RowBased
   * @group RowBased.loadNoParams
   * @covers STJ\Database\Dbo\RowBased::_performLoad
   * @expectedException STJ\Database\Dbo\RowBasedException
   * @expectedExceptionMessage No unique criteria to load from
   */
  public function loadNoParams() {
    $foo = new FooFailRowBased();
    $method = new ReflectionMethod($foo, '_performLoad');
    $method->setAccessible(true);

    // Load without anything
    $method->invoke($foo, false);
  }

  /**
   * @test
   * @group RowBased
   * @group RowBased.createSuccess
   * @covers STJ\Database\Dbo\RowBased::_performCreate
   */
  public function createSuccess() {
    $foo = new FooBarRowBased();
    $method = new ReflectionMethod($foo, '_performCreate');
    $method->setAccessible(true);

    // Create sample value
    $title = 'test: ' . __METHOD__;
    $foo->title = $title;
    $method->invoke($foo);

    // Make sure we have an ID
    $this->assertGreaterThan(0, $foo->example_id);

    // Make sure it was inserted into the db
    $stmt = self::$pdo->prepare('SELECT title FROM FOOBARROWBASED WHERE example_id = ?');
    $stmt->execute(array($foo->example_id));

    // Check the value
    $this->assertEquals($title, $stmt->fetchColumn(0));
  }

  /**
   * @test
   * @group RowBased
   * @group RowBased.updateSuccess
   * @covers STJ\Database\Dbo\RowBased::_performUpdate
   */
  public function updateSuccess() {
    $foo = new FooBarRowBased();
    $method = new ReflectionMethod($foo, '_performUpdate');
    $method->setAccessible(true);

    // Create sample value
    $title = 'test: ' . __METHOD__;
    $stmt = self::$pdo->prepare("INSERT INTO FOOBARROWBASED (`title`) VALUES (?)");
    $stmt->execute(array($title));

    // Get saved ID and title
    $foo->example_id = self::$pdo->lastInsertId();
    $foo->title = $title;
    $foo->clean();

    $title .= mt_rand(0, 5000);
    $foo->title = $title;
    $this->assertTrue($method->invoke($foo));

    // Make sure it was updated into the db
    $stmt = self::$pdo->prepare('SELECT title FROM FOOBARROWBASED WHERE example_id = ?');
    $stmt->execute(array($foo->example_id));

    // Check the value
    $this->assertEquals($title, $stmt->fetchColumn(0));
  }

  /**
   * @test
   * @group RowBased
   * @group RowBased.updateSkip
   * @covers STJ\Database\Dbo\RowBased::_performUpdate
   */
  public function updateSkip() {
    $foo = new FooBarRowBased();
    $method = new ReflectionMethod($foo, '_performUpdate');
    $method->setAccessible(true);

    // Set ID and title
    $foo->example_id = 15;
    $foo->title = __METHOD__;
    $foo->clean();

    // Do nothing, nothing has changed
    $this->assertFalse($method->invoke($foo));
  }

  /**
   * @test
   * @group RowBased
   * @group RowBased.updateFailure
   * @covers STJ\Database\Dbo\RowBased::_performUpdate
   * @expectedException STJ\Database\Dbo\RowBasedException
   * @expectedExceptionMessage No unique criteria to load from
   */
  public function updateFailure() {
    $foo = new FooFailRowBased();
    $method = new ReflectionMethod($foo, '_performUpdate');
    $method->setAccessible(true);

    // Set title, skip ID
    $foo->title = __METHOD__;

    // Invoke method, expect failure
    $method->invoke($foo);
  }

  /**
   * @test
   * @group RowBased
   * @group RowBased.deleteSuccess
   * @covers STJ\Database\Dbo\RowBased::_performDelete
   */
  public function deleteSuccess() {
    $foo = new FooBarRowBased();
    $method = new ReflectionMethod($foo, '_performDelete');
    $method->setAccessible(true);

    // Create sample value
    $title = 'test: ' . __METHOD__;
    $stmt = self::$pdo->prepare("INSERT INTO FOOBARROWBASED (`title`) VALUES (?)");
    $stmt->execute(array($title));

    // Get saved ID and title
    $foo->example_id = self::$pdo->lastInsertId();
    $foo->clean();
    $foo->markAsNew(false);

    // Delete the object
    $method->invoke($foo);

    // Make sure it was removed from the db
    $stmt = self::$pdo->prepare('SELECT count(*) FROM FOOBARROWBASED WHERE example_id = ?');
    $stmt->execute(array($foo->example_id));

    // Check the value
    $this->assertEquals(0, $stmt->fetchColumn(0));
  }

  /**
   * @test
   * @group RowBased
   * @group RowBased.deleteFailure
   * @covers STJ\Database\Dbo\RowBased::_performDelete
   * @expectedException STJ\Database\Dbo\RowBasedException
   * @expectedExceptionMessage No unique criteria to load from
   */
  public function deleteFailure() {
    $foo = new FooFailRowBased();
    $method = new ReflectionMethod($foo, '_performDelete');
    $method->setAccessible(true);

    // Set title, skip ID
    $foo->title = __METHOD__;

    // Invoke method, expect failure
    $method->invoke($foo);
  }

  /**
   * @test
   * @group RowBased
   * @group RowBased.loadMany
   * @covers STJ\Database\Dbo\RowBased::loadMany
   */
  public function loadMany() {
    // Create sample values
    $titles = array();

    $stmt = self::$pdo->prepare("INSERT INTO FOOBARROWBASED (`title`,`counter`) VALUES (?,?)");
    foreach (range(1,20) as $i) {
      $title = __METHOD__ . $i;
      $stmt->execute(array($title, $i));
      $titles[self::$pdo->lastInsertId()] = $title;
    }

    // Load objects
    $objects = FooBarRowBased::loadMany(array('title' => array_values($titles)));

    // Ensure we got the right number
    $this->assertEquals(count($objects), count($titles));

    // Check the objects
    foreach ($objects as $object) {
      $this->assertArrayHasKey($object->example_id, $titles);
      $this->assertEquals($titles[$object->example_id], $object->title);
      $this->assertFalse($object->isNew());
    }

    // Try again with RW
    $objects = FooBarRowBased::loadMany(array('title' => array_values($titles)), true);

    // Ensure we got the right number
    $this->assertEquals(count($objects), count($titles));

    // Check the objects
    foreach ($objects as $object) {
      $this->assertArrayHasKey($object->example_id, $titles);
      $this->assertEquals($titles[$object->example_id], $object->title);
      $this->assertFalse($object->isNew());
    }

    // Try again with a limit
    $objects = FooBarRowBased::loadMany(array('title' => array_values($titles)), false, 1);

    // Ensure we got the right number
    $this->assertEquals(count($objects), 1);
  }
}

class RowBasedMock extends RowBased {
  public static function leverage($method, array $args = array()) {
    return call_user_func_array('static::' . $method, $args);
  }

  public function clean() {
    return $this->_migrateDirtyToClean();
  }
}

class FooBarRowBased extends RowBasedMock {}

class FooFailRowBased extends RowBasedMock {}

class fooBT extends RowBasedMock {
  protected static $_belongs_to = array('fooHA');
}
class fooHA extends RowBasedMock {
  protected static $_has_a = array('fooBT');
}
class fooHM extends RowBasedMock {
  protected static $_has_many = array('fooHMH');
}
class fooHMH extends RowBasedMock {
  protected static $_belongs_to = array('fooHM');
}
class fooHMTB extends RowBasedMock {
  protected static $_has_many_through = array('fooHMTE' => 'fooHMTM');
}
class fooHMTM extends RowBasedMock {
  protected static $_belongs_to = array('fooHMTB','fooHMTE');
}
class fooHMTE extends RowBasedMock {
  protected static $_has_many_through = array('fooHMTB' => 'fooHMTM');
}