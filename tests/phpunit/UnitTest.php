<?php

/* PHPUnit Test Case */
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Unit Test Defaults
 */
class UnitTest extends PHPUnit_Framework_TestCase {
  public $pdo;

  public function setUp() {
    // Set hostname
    $_SERVER['HTTP_HOST'] = 'stj.me';

    // Initialize Settings
//    \STJ\Core\Conf::init('conf/settings.ini');

    // Clear session
    @session_destroy();

    // Create PDO Object
//    $this->pdo = new PDO(\STJ\Core\Conf::database('dsn'),
//                     \STJ\Core\Conf::database('username'),
//                     \STJ\Core\Conf::database('password'));
//    $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//    $this->pdo->setAttribute(PDO::ATTR_FETCH_TABLE_NAMES, 1);

    // Setup connectors
    //\SG\MVC\Model::setDbRW($pdo);
    //\SG\MVC\Model::setDbRO($pdo);
  }
}