<?php

namespace STJ\Core;

use ReflectionMethod;

/**
 * Autoload Test
 *
 * @see https://github.com/stjohnjohnson/stj-core-php
 */
class AutoloadTest extends \UnitTest {
  /**
   * @test
   * @group Autoload
   * @group Autoload.caseSensitive
   * @covers STJ\Core\Autoload
   */
  public function caseSensitive() {
    // Check normal case
    $actualfile = 'Folder/File.php';
    AutoloadMockEquals::$mockEquals = $actualfile;
    $this->assertEquals($actualfile, AutoloadMockEquals::classToFile('Folder\\File'));

    // Check lowercase folders
    $actualfile = 'folder/File.php';
    AutoloadMockEquals::$mockEquals = $actualfile;
    $this->assertEquals($actualfile, AutoloadMockEquals::classToFile('Folder\\File'));

    // Check lowercase all
    $actualfile = 'folder/file.php';
    AutoloadMockEquals::$mockEquals = $actualfile;
    $this->assertEquals($actualfile, AutoloadMockEquals::classToFile('Folder\\File'));

    // Check failure
    $actualfile = 'Folder/File.php';
    AutoloadMockEquals::$mockEquals = $actualfile;
    $this->assertFalse(AutoloadMockEquals::classToFile('folder\\file'));
  }

  /**
   * @test
   * @group Autoload
   * @group Autoload.isFile
   * @covers STJ\Core\Autoload
   */
  public function isFile() {
    // Put dummy class
    $filename = '/tmp/AutoloadTest.php';
    file_put_contents($filename, '<?php class AutoloadExists {}');

    $method = new ReflectionMethod(
      'STJ\\Core\\Autoload', '_isFile'
    );
    $method->setAccessible(true);

    // Test failure
    $this->assertFalse($method->invoke(null, '/tmp/doesnotexist.' . sha1(time())));

    // Test success
    $this->assertTrue($method->invoke(null, $filename));
  }

  /**
   * @test
   * @group Autoload
   * @group Autoload.load
   * @covers STJ\Core\Autoload
   */
  public function load() {
    set_include_path(ini_get('include_path') . ':/tmp');
    // Put dummy class
    $filename = '/tmp/AutoloadExists.php';
    file_put_contents($filename, '<?php class AutoloadExists {}');
    $class = 'AutoloadExists';

    // Check failure first
    $this->assertFalse(Autoload::load('doesnotexist.' . sha1(time())));

    // Check success!
    $this->assertTrue(Autoload::load($class));
    $this->assertTrue(class_exists($class, false));
  }
}

class AutoloadMockEquals extends Autoload {
  public static $mockEquals = '';

  protected static function _isFile($filename) {
    if (self::$mockEquals == $filename) {
      return true;
    }
    return false;
  }
}
