<?php

namespace STJ\MVC;

use PHPUnit_Framework_TestCase,
    Exception,
    ReflectionMethod,
    STJ\Core\Route;

/**
 * Controller Test
 */
class ControllerTest extends PHPUnit_Framework_TestCase {
  const VIEW_FILE = '/tmp/stj-view.phtml';
  const LAYOUT_FILE = '/tmp/stj-layout.phtml';

  /**
   * One-Time setup
   */
  public static function setUpBeforeClass() {
    // Add access to /tmp
    set_include_path(get_include_path() . PATH_SEPARATOR . '/tmp');

    // Create sample files with PHP in it
    file_put_contents(self::VIEW_FILE, 'Hello, <?= isset($this->name) ? $this->name : "World" ?>!');
    file_put_contents(self::LAYOUT_FILE, 'Title: <?= $this->title ?>, Page: <?= $this->page ?>, Action: <?= $this->action ?>, Content: <?= $this->content ?>');
  }

  /**
   * @test
   * @group Controller
   * @group Controller.dispatch
   * @covers STJ\MVC\Controller::dispatch
   * @covers STJ\MVC\Controller::execute
   * @covers STJ\MVC\Controller::__construct
   * @covers STJ\MVC\Controller::_init
   * @covers STJ\MVC\Controller::_authCheck
   * @dataProvider dispatchProvider
   */
  public function dispatch($uri, $expected = array(), $output = 'Hello, World!', $exception = false, $exType = 'STJ\\MVC\\ControllerException') {
    $_SERVER['REQUEST_URI'] = $uri;
    Route::reload(true);

    ControllerMock::reset();
    if ($exception) {
      $this->setExpectedException($exType, $exception);
    }
    $this->assertEquals($output, Controller::dispatch('defaultcontrollermock', __NAMESPACE__, ''));
    $this->assertEquals($expected, ControllerMock::$calls);
  }

  /**
   * Provider for dispatch test
   *
   * @return array
   */
  public function dispatchProvider() {
    $cases = array();

    // Default page
    $cases[] = array('/', array(
       'STJ\MVC\ControllerMock::_init' => array(true),
       'STJ\MVC\ControllerMock::_authCheck' => array('index'),
       'STJ\MVC\ControllerMock::index' => array(true)
    ));
    // Default action
    $cases[] = array('/controllermock', array(
       'STJ\MVC\ControllerMock::_init' => array(true),
       'STJ\MVC\ControllerMock::_authCheck' => array('index'),
       'STJ\MVC\ControllerMock::index' => array(true)
    ));
    // Simple Example
    $cases[] = array('/controllermock/index', array(
       'STJ\MVC\ControllerMock::_init' => array(true),
       'STJ\MVC\ControllerMock::_authCheck' => array('index'),
       'STJ\MVC\ControllerMock::index' => array(true)
    ));
    // Missing controller
    $cases[] = array('/noexists', array(), '',
        "Controller 'noexists' not found");
    // Invalid controller type
    $cases[] = array('/badcontrollermock', array(), '',
        "Controller 'badcontrollermock' is not a valid Controller");
    // Invalid action
    $cases[] = array('/controllermock/missing', array(), '',
        "Action 'missing' not found for Controller 'controllermock'");
    // Private action
    $cases[] = array('/controllermock/_private', array(), '',
        "Action '_private' not available for Controller 'controllermock'");
    // Protected action
    $cases[] = array('/controllermock/_protected', array(), '',
        "Action '_protected' not available for Controller 'controllermock'");
    // Hidden action
    $cases[] = array('/controllermock/redirectto', array(), '',
        "Action 'redirectto' not available for Controller 'controllermock'");
    // Valid action
    $cases[] = array('/controllermock/find', array(
       'STJ\MVC\ControllerMock::_init' => array(true),
       'STJ\MVC\ControllerMock::_authCheck' => array('find'),
       'STJ\MVC\ControllerMock::find' => array(true)
    ));
    // Required Route
    $cases[] = array('/controllermock/view/', array(), '',
        "Routing '/controllermock/view' doesn't match required '/controllermock/view/:id'");
    // Working Route
    $cases[] = array('/controllermock/view/14', array(
       'STJ\MVC\ControllerMock::_init' => array(true),
       'STJ\MVC\ControllerMock::_authCheck' => array('view'),
        'STJ\MVC\ControllerMock::view' => array('14')
    ));
    // Longer Route (with optional)
    $cases[] = array('/controllermock/delete', array(), '',
        "Routing '/controllermock/delete' doesn't match required '/controllermock/delete/:id/::recursive'");
    // Failed Auth Check
    $cases[] = array('/controllermock/failauth', array(), '',
        "Access Denied to controllermock.failauth");
    // Optional Route
    $cases[] = array('/controllermock/delete/1', array(
       'STJ\MVC\ControllerMock::_init' => array(true),
       'STJ\MVC\ControllerMock::_authCheck' => array('delete'),
       'STJ\MVC\ControllerMock::delete' => array('10')
    ));
    // Optional Route
    $cases[] = array('/controllermock/delete/1/something', array(
       'STJ\MVC\ControllerMock::_init' => array(true),
       'STJ\MVC\ControllerMock::_authCheck' => array('delete'),
       'STJ\MVC\ControllerMock::delete' => array('1something')
    ));
    // Redirecting (no view generated)
    $cases[] = array('/controllermock/redirect', array(
       'STJ\MVC\ControllerMock::_init' => array(true),
       'STJ\MVC\ControllerMock::_authCheck' => array('redirect'),
    'STJ\MVC\ControllerMock::redirect' => array(true)
    ), '');
    // Use a layout
    $cases[] = array('/layoutcontrollermock/view/1', array(
       'STJ\MVC\ControllerMock::_init' => array(true),
       'STJ\MVC\ControllerMock::_authCheck' => array('view'),
        'STJ\MVC\ControllerMock::view' => array('1')
    ), 'Title: Viewing #1, Page: layoutcontrollermock, Action: view, Content: Hello, World!');
    // Default view
    $cases[] = array('/controllermock/resetview', array(
       'STJ\MVC\ControllerMock::_init' => array(true),
       'STJ\MVC\ControllerMock::_authCheck' => array('resetview'),
        'STJ\MVC\ControllerMock::resetview' => array(true)
    ), '', 'Unable to find view: \controllermock\resetview', 'STJ\\MVC\\ViewException');
    // Returned Output
    $cases[] = array('/controllermock/output/Bob', array(
       'STJ\MVC\ControllerMock::_init' => array(true),
       'STJ\MVC\ControllerMock::_authCheck' => array('output'),
        'STJ\MVC\ControllerMock::output' => array('Bob')
    ), 'Hello, Bob!');

    return $cases;
  }

  /**
   * @test
   * @group Controller
   * @group Controller.headers
   * @covers STJ\MVC\Controller::_setHeader
   * @covers STJ\MVC\Controller::_getHeaders
   */
  public function headers() {
    $controller = new ControllerMock();

    $setMethod = new ReflectionMethod($controller, '_setHeader');
    $setMethod->setAccessible(true);

    $getMethod = new ReflectionMethod($controller, '_getHeaders');
    $getMethod->setAccessible(true);

    // Reset headers
    ControllerMock::reset();

    $setMethod->invoke($controller, __METHOD__);
    $this->assertContains(__METHOD__, $getMethod->invoke($controller));
  }

  /**
   * @test
   * @group Controller
   * @group Controller.redirectTo
   * @covers STJ\MVC\Controller::redirectTo
   * @dataProvider redirectProvider
   */
  public function redirectTo($url, $page, $action = null, array $params = array(), $fragment = '') {
    $controller = new ControllerMock();
    $_SERVER['HTTP_HOST'] = 'stj.me';

    $this->assertEquals($url, $controller->redirectTo($page, $action, $params, $fragment));
  }

  /**
   * @test
   * @group Controller
   * @group Controller.redirectToUrl
   * @covers STJ\MVC\Controller::redirectToUrl
   * @dataProvider redirectProvider
   */
  public function redirectToUrl($url) {
    $controller = new ControllerMock();
    $method = new ReflectionMethod($controller, '_getHeaders');
    $method->setAccessible(true);

    // Reset headers
    ControllerMock::reset();

    // Test to ensure we get the url
    $this->assertEquals($url, $controller->redirectToURL($url));
    $this->assertContains('Location: ' . $url, $method->invoke($controller));
  }

  /**
   * Provider for redirect
   *
   * @return array
   */
  public function redirectProvider() {
    $cases = array();

    // Controller Object
    $cases[] = array('http://stj.me/controllermock', new ControllerMock());

    // Default Action
    $cases[] = array('http://stj.me/sample', 'sample');

    // With Action
    $cases[] = array('http://stj.me/sample/action', 'sample', 'action');

    // With Params
    $cases[] = array('http://stj.me/sample/action?query=fish', 'sample', 'action', array('query' => 'fish'));

    // With Frag
    $cases[] = array('http://stj.me/sample/action?query=fish#footer', 'sample', 'action', array('query' => 'fish'), 'footer');

    return $cases;
  }

  /**
   * @test
   * @group Controller
   * @group Controller.getSetters
   * @covers STJ\MVC\Controller::setTitle
   * @covers STJ\MVC\Controller::getTitle
   * @covers STJ\MVC\Controller::setView
   * @covers STJ\MVC\Controller::getView
   * @covers STJ\MVC\Controller::setLayout
   * @covers STJ\MVC\Controller::getLayout
   * @covers STJ\MVC\Controller::getPage
   */
  public function getSetters() {
    $controller = new ControllerMock();

    // Title
    $controller->setTitle(__METHOD__);
    $this->assertEquals(__METHOD__, $controller->getTitle());

    // View
    $controller->setView(__METHOD__);
    $this->assertEquals(__METHOD__, $controller->getView());

    // Layout
    $controller->setLayout(__METHOD__);
    $this->assertEquals(__METHOD__, $controller->getLayout());

    // Page
    $this->assertEquals('controllermock', $controller->getPage());
  }
}

class ControllerMock extends Controller {
  protected $_view = 'stj-view';
  protected $_layout = null;

  public static $calls = array();

  public static function reset() {
    self::$calls = array();
    self::$_headers = array();
  }

  protected function _init() {
    self::$calls[__METHOD__][] = true;
    return parent::_init();
  }

  protected function _authCheck($action) {
    self::$calls[__METHOD__][] = $action;
    if ($action === 'failauth') {
      return false;
    }
    return parent::_authCheck($action);
  }

  public function index() {
    self::$calls[__METHOD__][] = true;
  }

  public function find() {
    self::$calls[__METHOD__][] = true;
  }

  public function failauth() {
    self::$calls[__METHOD__][] = true;
  }

  public function view($id) {
    $this->setTitle('Viewing #' . $id);
    self::$calls[__METHOD__][] = $id;
  }

  public function delete($id, $recursive = 0) {
    self::$calls[__METHOD__][] = $id . $recursive;
  }

  public function redirect() {
    self::$calls[__METHOD__][] = true;
    self::_setHeader('Location: http://reddit.com/');
  }

  protected function _protected() {
    self::$calls[__METHOD__][] = true;
  }

  private function _private() {
    self::$calls[__METHOD__][] = true;
  }

  public function resetview() {
    $this->setView(null);
    self::$calls[__METHOD__][] = true;
  }

  public function output($input) {
    self::$calls[__METHOD__][] = $input;
    return array('name' => $input);
  }
}

class LayoutControllerMock extends ControllerMock {
  protected $_layout = 'stj-layout';
}

class DefaultControllerMock extends ControllerMock {}

class BadControllerMock extends Exception {}