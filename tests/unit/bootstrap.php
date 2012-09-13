<?php
/**
 * Boostrap for Unit tests
 */

// Set include dir to base and pear
$base = realpath(__DIR__ . '/../../');
$pear = realpath($base . '/pear/');
set_include_path(implode(PATH_SEPARATOR, array(get_include_path(), $base, $pear)));

// Set smart autoloader (just for tests)
spl_autoload_register(function($class) {
  // We want the path and class seperate
  $namespace = explode('\\', $class);
  $classname = array_pop($namespace);

  // Remove STJ from namespace
  if (isset($namespace[0]) && $namespace[0] === 'STJ'){
    unset($namespace[0]);
  }

  // Compress namespace back
  $namespace = implode('/', $namespace);
  // Lowercase
  $namespace = strtolower($namespace);

  // Try to load file
  var_dump("$namespace/$classname.php");
  @include "$namespace/$classname.php";
});

// Load Unit Test
require_once $base . '/tests/phpunit/UnitTest.php';

// Disable session cookie support
ini_set('session.use_cookies', '0');
@session_cache_limiter('');
@session_destroy();

// Nothing more to see here