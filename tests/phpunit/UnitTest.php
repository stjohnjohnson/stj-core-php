<?php

/* PHPUnit Test Case */
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Unit Test Defaults
 */
class UnitTest extends PHPUnit_Framework_TestCase {
  public function setUp() {
    // Set hostname
    $_SERVER['HTTP_HOST'] = 'stj.me';

    // Initialize Settings
//    \STJ\Core\Conf::init('conf/settings.ini');

    // Clear session
    @session_destroy();
  }
}