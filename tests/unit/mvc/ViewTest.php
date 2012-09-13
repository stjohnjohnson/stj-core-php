<?php

namespace STJ\MVC;

use PHPUnit_Framework_TestCase,
    Exception;

/**
 * View Test
 */
class ViewTest extends PHPUnit_Framework_TestCase {
  const FILE_GOOD = '/tmp/stj-view-good.phtml';
  const FILE_VARS = '/tmp/stj-view-vars.phtml';
  const FILE_EXCP = '/tmp/stj-view-exception.phtml';
  const FILE_MISS = '/tmp/stj-view-missing.phtml';

  /**
   * One-Time setup
   */
  public static function setUpBeforeClass() {
    // Add access to /tmp
    set_include_path(get_include_path() . PATH_SEPARATOR . '/tmp');

    // Create sample file with PHP in it
    file_put_contents(self::FILE_GOOD, 'Welcome to <?php echo date("Y") ?>, World!');
    file_put_contents(self::FILE_VARS, 'Hello, <?php echo $this->name ?>!');
    file_put_contents(self::FILE_EXCP, 'ERROR, <?php throw new \\STJ\\MVC\\ViewException("This is an error", 42);');
  }

  /**
   * @test
   * @group View
   * @group View.construct
   * @covers STJ\MVC\View::__construct
   */
  public function construct() {
    $view = new View(self::FILE_GOOD, array(
        'foo' => 'bar'
    ));

    $this->assertEquals('bar', $view->foo);
  }

  /**
   * @test
   * @group View
   * @group View.toString
   * @covers STJ\MVC\View::render
   */
  public function toString() {
    $view = new View(self::FILE_GOOD);

    $this->assertEquals('Welcome to ' . date('Y') . ', World!', $view->render());
  }

  /**
   * @test
   * @group View
   * @group View.toStringVars
   * @covers STJ\MVC\View::render
   */
  public function toStringVars() {
    $view = new View(self::FILE_VARS, array(
        'name' => 'Bob Dole'
    ));

    $this->assertEquals('Hello, Bob Dole!', $view->render());
  }

  /**
   * @test
   * @group View
   * @group View.toStringException
   * @covers STJ\MVC\View::render
   * @expectedException STJ\MVC\ViewException
   * @expectedExceptionMessage This is an error
   */
  public function toStringException() {
    $view = new View(self::FILE_EXCP);

    $this->assertEquals('', $view->render());
  }

  /**
   * @test
   * @group View
   * @group View.generate
   * @covers STJ\MVC\View::generate
   */
  public function generate() {
    $this->assertEquals('Welcome to ' . date('Y') . ', World!',
            View::generate('stj-view-good', array(), ''));
  }

  /**
   * @test
   * @group View
   * @group View.generateVars
   * @covers STJ\MVC\View::generate
   */
  public function generateVars() {
    $this->assertEquals('Hello, Bob Dole!', View::generate('stj-view-vars', array(
          'name' => 'Bob Dole'), ''));
  }

  /**
   * @test
   * @group View
   * @group View.generateMissing
   * @covers STJ\MVC\View::generate
   * @expectedException STJ\MVC\ViewException
   * @expectedExceptionMessage Unable to find view: \stj-view-missing
   */
  public function generateMissing() {
    View::generate('stj-view-missing', array(), '');
  }
}