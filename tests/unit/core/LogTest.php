<?php

namespace STJ\Core;

use Exception,
    ReflectionMethod,
    ReflectionProperty;

/**
 * Log Test
 *
 * @see https://github.com/stjohnjohnson/stj-core-php
 */
class LogTest extends \UnitTest {
  public static $newFile = '/tmp/LogTest.log';
  public static $oldLevel = null;
  public static $oldFile = null;

  /**
   * Change log location and store existing location and level
   */
  public static function setUpBeforeClass() {
    self::$oldFile = ini_get('error_log');
    self::$oldLevel = Log::getLimit();
    ini_set('error_log', self::$newFile);

    date_default_timezone_set('UTC');
  }

  /**
   * Reset the log location and level
   */
  public static function tearDownAfterClass() {
    self::clearLog();
    ini_set('error_log', self::$oldFile);
    Log::setLimit(self::$oldLevel);
  }

  /**
   * Clears the log before each run
   * Also ensures we can write to the log
   */
  public function setUp() {
    self::clearLog();
  }

  /**
   * Clears the log contents
   */
  private static function clearLog() {
    file_put_contents(self::$newFile, '');
  }

  /**
   * Quick way to check the file for the specific regex we're looking for
   * Automagically includes the time and type check
   *
   * @param string $type
   * @param string $message
   */
  private function assertFileContainsRegex($type, $message) {
    $contents = trim(file_get_contents(self::$newFile));
    $this->assertRegExp('/\[\d{2}-\w+-\d{4} \d+:\d{2}:\d{2}(| UTC)\] \[' . $type . '\] ' . $message . '/', $contents);
    self::clearLog();
  }

  /**
   * @test
   * @group Log
   * @group Log.storeLimits
   * @covers STJ\Core\Log
   * @dataProvider storeLimitsProvider
   */
  public function storeLimits($input, $output) {
    // Default to warning
    Log::setLimit(Log::WARNING);

    // Run test
    Log::setLimit($input);
    $this->assertEquals($output, Log::getLimit());
  }

  /**
   * Data Provider for storeLimits
   *
   * @return array of use cases
   */
  public function storeLimitsProvider() {
    $cases = array();

    // Good
    $cases[] = array(Log::DISABLED, Log::DISABLED);
    $cases[] = array(Log::DEBUG, Log::DEBUG);
    $cases[] = array(Log::QUERY, Log::QUERY);
    $cases[] = array(Log::INFO, Log::INFO);
    $cases[] = array(Log::WARNING, Log::WARNING);
    $cases[] = array(Log::ERROR, Log::ERROR);

    // Bad
    $cases[] = array(1232, Log::WARNING);
    $cases[] = array('2323', Log::WARNING);
    $cases[] = array(null, Log::WARNING);

    return $cases;
  }

  /**
   * @test
   * @group Log
   * @group Log.limits
   * @covers STJ\Core\Log
   * @dataProvider limitsProvider
   */
  public function limits($level, $count) {
    // Set limit
    Log::setLimit($level);

    // Test range
    Log::error('test');
    Log::warning('test');
    Log::info('test');
    Log::debug('test');
    Log::query('test');

    // Check limit
    $contents = trim(file_get_contents(self::$newFile));
    if (empty($contents)) {
      $this->assertEquals($count, 0);
    } else {
      $this->assertEquals($count, count(explode(PHP_EOL, $contents)));
    }
  }

  /**
   * Data Provider for limits
   *
   * @return array of test cases
   */
  public function limitsProvider() {
    $cases = array();

    // Query (very verbose)
    $cases[] = array(Log::QUERY, 5);
    // Debug (4 out of 5)
    $cases[] = array(Log::DEBUG, 4);
    // Info (3 out of 5)
    $cases[] = array(Log::INFO, 3);
    // Warning (3 out of 5)
    $cases[] = array(Log::WARNING, 2);
    // Error (3 out of 5)
    $cases[] = array(Log::ERROR, 1);
    // Disabled (0 out of 5)
    $cases[] = array(Log::DISABLED, 0);

    return $cases;
  }

  /**
   * @test
   * @group Log
   * @group Log.write
   * @covers STJ\Core\Log
   * @dataProvider writeProvider
   */
  public function write($object, $expected, $type = Log::INFO) {
    // Set current limit
    Log::setLimit($type);

    // Make method available
    $method = new ReflectionMethod(
      'STJ\\Core\\Log', '_write'
    );
    $method->setAccessible(true);

    // Make label available
    $property = new ReflectionProperty(
      'STJ\\Core\\Log', '_LABELS'
    );
    $property->setAccessible(true);
    $translate = $property->getValue();

    // Execute method
    $method->invoke(null, $type, $object);
    // Assert it was done
    $this->assertFileContainsRegex($translate[$type], $expected);
  }

  /**
   * Data Provider for write
   *
   * @return array of test cases
   */
  public function writeProvider() {
    $cases = array();

    // Strings
    $cases[] = array('string', 'string');
    // Numbers
    $cases[] = array(123456, '123456');
    // Arrays
    $cases[] = array(array(1,2,4,5), 'array \(\n  0 => 1,\n  1 => 2,\n  2 => 4,\n  3 => 5,\n\)');
    // Associative Arrays
    $cases[] = array(array('foo' => 'bar'), 'array \(\n  \'foo\' => \'bar\',\n\)');
    // Objects
    $cases[] = array((object) array('prop' => 'test'), 'stdClass::__set_state\(array\(\n   \'prop\' => \'test\',\n\)\)');
    // Exceptions
    $cases[] = array(new Exception('Testing 123', 42), '\[Exception:42\] Testing 123');
    // Exceptions (as warning)
    $cases[] = array(new Exception('Testing 456', 15), '\[Exception:15\] Testing 456 \(file: .+ @ line: \d+\)\n#0 \[internal function\]: ', Log::WARNING);

    // Strings (as query)
    $cases[] = array('Banana', 'Banana$', Log::QUERY);
    // Strings (as debug)
    $cases[] = array('Banana', 'Banana$', Log::DEBUG);
    // Strings (as info)
    $cases[] = array('Banana', 'Banana$', Log::INFO);
    // Strings (as warning)
    $cases[] = array('Banana', 'Banana \(file: .+ @ line: \d+\)', Log::WARNING);
    // Strings (as error)
    $cases[] = array('Banana', 'Banana \(file: .+ @ line: \d+\)', Log::ERROR);

    return $cases;
  }
}
