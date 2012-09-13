<?php

namespace STJ\MVC;

use STJ\Core\Route,
    STJ\Core\HTML,
    STJ\Core\Request,
    Exception,
    ReflectionMethod;

/**
 * Web Controller
 *
 * Generates html page based on layout and view files.  Actions are
 * based on function names.
 *
 * @see https://github.com/stjohnjohnson/stj-core-php
 */
abstract class Controller {
  protected static $_headers = array();
  protected $_layout = 'default';
  protected $_view = null;
  protected $_title = null;

  /**
   * Constructor
   */
  public function __construct() {
    $this->_init();
  }

  /**
   * Controller Initializer
   */
  protected function _init() {}

  /**
   * Run check against action
   *
   * @param string $action
   *   Action Being Run
   * @return bool
   *   Continue or Not
   */
  protected function _authCheck($action) {
    return true;
  }

  /**
   * Set Layout File
   *
   * @param string $layout
   *   New layout to set - Null = no layout
   */
  public function setLayout($layout = null) {
    $this->_layout = $layout;
  }

  /**
   * Retreive Layout File
   *
   * @return string
   */
  public function getLayout() {
    return $this->_layout;
  }

  /**
   * Set View File
   *
   * @param string $view
   *   New view to set
   */
  public function setView($view) {
    $this->_view = $view;
  }

  /**
   * Retreive View File
   *
   * @return string
   */
  public function getView() {
    return $this->_view;
  }

  /**
   * Set Page Title
   *
   * @param string $title
   *   New title to set
   */
  public function setTitle($title) {
    $this->_title = $title;
  }

  /**
   * Retreive Page Title
   *
   * @return string
   */
  public function getTitle() {
    return $this->_title;
  }

  /**
   * Retreive Page Name
   *
   * @return string
   */
  public function getPage() {
    return strtolower(array_pop(explode('\\', get_class($this))));
  }

  /**
   * Sets a Header
   *
   * @param string $header
   *   Header to set ex. 'Location: about:blank'
   */
  protected static function _setHeader($header) {
    self::$_headers[] = $header;
    @header($header);
  }

  /**
   * Returns list of Sent Headers
   *
   * @return array
   *   Array of Headers Sent
   */
  protected static function _getHeaders() {
    return self::$_headers;
  }

  /**
   * Externally Redirects to another controller/action
   *
   * @note Can pass $this or Controller object as $controller
   * @param string $controller
   *   New Controller
   * @param string $action
   *   New Action
   * @param array $params
   *   Get Parameters
   * @param string $fragment
   *   Hash to load
   * @return string
   *   Redirect URL
   */
  public function redirectTo($controller, $action = null, array $params = array(), $fragment = '') {
    // Support translating Objects to Controller
    if (is_object($controller) && is_subclass_of($controller, __CLASS__)) {
      $controller = $controller->getPage();
    }

    $routes = array($controller);
    // If we have an action, add it to the route
    if ($action !== null) {
      $routes[] = $action;
    }

    return $this->redirectToURL(HTML::url(Request::host() . '/', $routes, $params, $fragment));
  }

  /**
   * External Redirect to another URL
   *
   * @param string $url
   *   Url to redirect to
   * @return string
   *   Redirect URL
   */
  public function redirectToURL($url) {
    static::_setHeader("Location: $url");

    return $url;
  }

  /**
   * Universal controller dispatcher, executes/displays
   *
   * Uses data from Route: /page/action/
   *
   * @param string $default_page
   *   Default page to load
   * @param string $controllerNamespace
   *   Namespace for the Controllers
   * @param string $viewNamespace
   *   Namespace for the Views
   * @return string
   *   Output from action
   * @throws ControllerException
   */
  public static function dispatch($default_page = 'dashboard', $controllerNamespace = 'STJ\\Controllers', $viewNamespace = 'STJ\\Views') {
    // Load from URL
    $page = Route::get(0);
    $action = Route::get(1);

    // Default if not sent
    if (in_array($page, array('', null))) {
      $page = $default_page;
    }

    // Default to 'index'
    if (in_array($action, array('', null))) {
      $action = 'index';
    }

    // Add namespace to class
    $class = $controllerNamespace . '\\' . ucfirst($page);

    // Throw Exception on invalid controller
    if (!class_exists($class)) {
      throw new ControllerException("Controller '$page' not found", 404);
    }

    // Create controller
    $controller = new $class();

    // Ensure we're dealing with a controller that
    if (!is_subclass_of($controller, __CLASS__)) {
      throw new ControllerException("Controller '$page' is not a valid Controller", 500);
    }

    // Get the Params from the URL - Ignore the first two parts
    $params = array_slice(Route::export(), 2);

    // Execute and display
    return self::execute($controller, $action, $params, $viewNamespace);
  }

  /**
   * Excutes method from a Controller
   *
   * @param Controller $controller
   *   Controller that is executing
   * @param string $action
   *   Action to execute
   * @param array $args
   *   Arguments to pass into the method
   * @param string $viewNamespace
   *   Namespace for the Views
   * @return string
   *   Output from action
   * @throws ControllerException
   */
  public static function execute(Controller $controller, $action, array $args = array(), $viewNamespace = 'STJ\\Views') {
    $page = $controller->getPage();

    // Throw Exception on invalid action
    if (!method_exists($controller, $action)) {
      throw new ControllerException("Action '$action' not found for Controller '$page'", 404);
    }

    // Validate we can call the method
    $method = new ReflectionMethod($controller, $action);

    // Validate that it's a public/non-static method of the controller (not parent)
    if (!$method->isPublic() || $method->getDeclaringClass()->getName() === __CLASS__ || $method->isStatic()) {
      throw new ControllerException("Action '$action' not available for Controller '$page'", 418);
    }

    // Check routing
    if ($method->getNumberOfParameters() > 0) {
      $required = 0;
      $route = array($page, $action);
      foreach ($method->getParameters() as $param) {
        if ($param->isOptional()) {
          $route[] = '::' . $param->getName();
        } else {
          $route[] = ':' . $param->getName();
          $required++;
        }
      }
      $route = '/' . implode('/', $route);

      // If we don't match the route, error
      if (count($args) < $required) {
        $found = '/' . implode('/', Route::export());
        throw new ControllerException("Routing '$found' doesn't match required '$route'", 404);
      }
    }

    // Do an Auth Check
    if (!$controller->_authCheck($action)) {
      throw new ControllerException("Access Denied to $page.$action", 403);
    }

    // Run method
    $output = $method->invokeArgs($controller, $args);
    if (!is_array($output)) {
      $output = array();
    }

    // Check if we're redirecting
    foreach (self::_getHeaders() as $header) {
      // Skip outputing if we're changing location
      if (strncasecmp($header, 'Location:', 9) === 0) {
        return;
      }
    }

    // Otherwise, render the page

    // Calculate the view
    $view = $controller->getView();
    $layout = $controller->getLayout();

    // Default view to page/action
    if ($view === null) {
      $view = $page . '/' . $action;
    }

    // Generate response
    $response = View::generate($view, $output, $viewNamespace);

    // If we have a layout, use it
    if ($layout !== null) {
      $response = View::generate($controller->getLayout(), array(
          'page' => $page,
        'action' => $action,
         'title' => $controller->getTitle(),
       'content' => $response
      ), $viewNamespace);
    }

    return $response;
  }
}

class ControllerException extends Exception {}