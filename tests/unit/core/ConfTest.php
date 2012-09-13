<?php

namespace STJ\Core;

/**
 * Conf Test
 *
 * @see https://github.com/stjohnjohnson/stj-core-php
 */
class ConfTest extends \UnitTest {
  const VALID_FILENAME = '/tmp/conf-sample.ini';
  const EMPTY_FILENAME = '/tmp/conf-empty.ini';

  /**
   * Create sample files with fun data
   */
  public function setUp() {
    $data = array(
        '; This is a comment',
        '[section1]',
        'index1=',
        'index2=something',
        '; More comments',
        '[section2]',
        'index1=somethingelse',
        'index2 = "strings are fun"',
        '[types]',
        'int = 42',
        'float = 3.14',
        'bool1 = true',
        'bool2 = false',
    );

    file_put_contents(self::VALID_FILENAME, implode(PHP_EOL, $data));
    file_put_contents(self::EMPTY_FILENAME, '');
  }

  /**
   * Remove sample files
   */
  public function tearDown() {
    unlink(self::VALID_FILENAME);
    unlink(self::EMPTY_FILENAME);
  }

  /**
   * @test
   * @group Conf
   * @group Conf.validData
   * @covers STJ\Core\Conf::init
   * @covers STJ\Core\Conf::get
   * @covers STJ\Core\Conf::__callStatic
   * @dataProvider ConfProvider
   */
  public function validData($section, $index, $expectedValue) {
    Conf::init(self::VALID_FILENAME);
    $this->assertEquals($expectedValue, Conf::get($section, $index));
    $this->assertEquals($expectedValue, Conf::$section($index));
  }

  /**
   * @test
   * @group Conf
   * @group Conf.missingSection
   * @covers STJ\Core\Conf::init
   * @covers STJ\Core\Conf::get
   * @dataProvider ConfProvider
   */
  public function missingSection($section, $index, $expectedValue) {
    Conf::init(self::EMPTY_FILENAME);
    $this->setExpectedException('STJ\\Core\\ConfException', "No Settings found for '$section.$index'");
    $this->assertEquals($expectedValue, Conf::get($section, $index));
  }

  /**
   * @test
   * @group Conf
   * @group Conf.missingSection
   * @covers STJ\Core\Conf::init
   * @covers STJ\Core\Conf::get
   * @covers STJ\Core\Conf::__callStatic
   * @dataProvider ConfProvider
   */
  public function defaultValues($section, $index, $expectedValue) {
    Conf::init(self::EMPTY_FILENAME);
    $this->assertEquals($expectedValue, Conf::get($section, $index, $expectedValue));
    $this->assertEquals($expectedValue, Conf::$section($index, $expectedValue));
  }

  /**
   * Data Provider for Conf Tests
   *
   * @return array
   */
  public function ConfProvider() {
    $cases = array();

    $cases[] = array('section1', 'index1', '');
    $cases[] = array('section1', 'index2', 'something');
    $cases[] = array('section2', 'index1', 'somethingelse');
    $cases[] = array('section2', 'index2', 'strings are fun');
    $cases[] = array('types', 'int', '42');
    $cases[] = array('types', 'float', '3.14');
    $cases[] = array('types', 'bool1', true);
    $cases[] = array('types', 'bool2', false);

    return $cases;
  }
}
