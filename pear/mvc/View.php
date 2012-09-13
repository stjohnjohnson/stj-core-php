<?php

namespace STJ\MVC;

use STJ\Core\Autoload,
    Exception;

/**
 * Simple View engine from phtml files
 *
 * @see https://github.com/stjohnjohnson/stj-core-php
 */
class View {
  protected $_filename;

  /**
   * Create new View
   *
   * @param string $filename
   *   Filename to load
   * @param array $object
   *   Hash of data to use
   */
  public function __construct($filename, array $object = array()) {
    // Set local variables
    foreach ($object as $key => $value) {
      $this->$key = $value;
    }

    // Set filename
    $this->_filename = $filename;
  }

  /**
   * Renders View
   *
   * @return string
   *   Output of file
   * @throws Exception
   */
  public function render() {
    // Start output buffering
    ob_start();

    try {
      include $this->_filename;
    } catch (Exception $ex) {
      // Clean output buffer, we don't want to see broken html
      ob_end_clean();
      throw $ex;
    }

    // Return output + stop output buffering
    return ob_get_clean();
  }

  /**
   * Generate a View
   *
   * @param string $page
   *   Page name (using namespaces), eg. home\index instead of /var/stj/views/home/index.phtml
   * @param array $object
   *   Hash of data to use
   * @param string $namespace
   *   Namespace of Views
   * @return string
   *   Output of file
   * @throws ViewException
   *   When view cannot be found (404)
   */
  public static function generate($page, array $object = array(), $namespace = 'STJ\\Views') {
    // Use autoloader to find view file
    $class = $namespace . Autoload::NAME_SEP . str_replace(Autoload::DIR_SEP, Autoload::NAME_SEP, $page);
    $view_file = Autoload::classToFile($class, '.phtml');

    // Throw exception if not found
    if ($view_file === false) {
      throw new ViewException("Unable to find view: $class", 404);
    }

    // Create new view
    $view = new View($view_file, $object);

    // Return string version
    return $view->render();
  }
}

class ViewException extends Exception {}