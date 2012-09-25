<?php

namespace STJ\Database\Dbo;

use Closure;

/**
 * Closures Test
 */
class ClosuresTest extends \UnitTest {
  /**
   * Runs specific closure with that value
   *
   * @param Closure $closure
   *   Closure to execute
   * @param varied $value
   *   Value to pass into the closure
   * @return bool
   */
  public static function runClosure(Closure $closure, $value) {
    return $closure($value);
  }

  /**
   * @test
   * @group Closures
   * @group Closures.isPositive
   * @covers STJ\Database\Dbo\Closures
   */
  public function isPositive() {
    $this->assertFalse($this->runClosure(Closures::isPositive(), -15));
    $this->assertTrue($this->runClosure(Closures::isPositive(), 500));
    $this->assertTrue($this->runClosure(Closures::isPositive(), 'abc'));
  }

  /**
   * @test
   * @group Closures
   * @group Closures.isInteger
   * @covers STJ\Database\Dbo\Closures
   */
  public function isInteger() {
    $this->assertFalse($this->runClosure(Closures::isInteger(), 'abc'));
    $this->assertFalse($this->runClosure(Closures::isInteger(), 0.25));

    $this->assertTrue($this->runClosure(Closures::isInteger(), 500));
    $this->assertTrue($this->runClosure(Closures::isInteger(), '123'));
    $this->assertTrue($this->runClosure(Closures::isInteger(), -15));
  }

  /**
   * @test
   * @group Closures
   * @group Closures.isFloat
   * @covers STJ\Database\Dbo\Closures
   */
  public function isFloat() {
    $this->assertFalse($this->runClosure(Closures::isFloat(), 'abc'));
    $this->assertFalse($this->runClosure(Closures::isFloat(), '12.2.3'));

    $this->assertTrue($this->runClosure(Closures::isFloat(), 500));
    $this->assertTrue($this->runClosure(Closures::isFloat(), -123));
    $this->assertTrue($this->runClosure(Closures::isFloat(), '12.3'));
    $this->assertTrue($this->runClosure(Closures::isFloat(), 15.2));
  }

  /**
   * @test
   * @group Closures
   * @group Closures.isBoolean
   * @covers STJ\Database\Dbo\Closures
   */
  public function isBoolean() {
    $this->assertFalse($this->runClosure(Closures::isBoolean(), 'abc'));
    $this->assertFalse($this->runClosure(Closures::isBoolean(), 2));
    $this->assertFalse($this->runClosure(Closures::isBoolean(), 5));

    $this->assertTrue($this->runClosure(Closures::isBoolean(), true));
    $this->assertTrue($this->runClosure(Closures::isBoolean(), false));
    $this->assertTrue($this->runClosure(Closures::isBoolean(), 0));
    $this->assertTrue($this->runClosure(Closures::isBoolean(), 1));
    $this->assertTrue($this->runClosure(Closures::isBoolean(), '0'));
    $this->assertTrue($this->runClosure(Closures::isBoolean(), '1'));
  }

  /**
   * @test
   * @group Closures
   * @group Closures.isValid
   * @covers STJ\Database\Dbo\Closures
   */
  public function isValid() {
    $this->assertFalse($this->runClosure(Closures::isValid(), ''));
    $this->assertFalse($this->runClosure(Closures::isValid(), '           '));
    $this->assertFalse($this->runClosure(Closures::isValid(), null));
    $this->assertFalse($this->runClosure(Closures::isValid(), array()));

    $this->assertTrue($this->runClosure(Closures::isValid(), 500));
    $this->assertTrue($this->runClosure(Closures::isValid(), '123'));
    $this->assertTrue($this->runClosure(Closures::isValid(), 'abc'));
    $this->assertTrue($this->runClosure(Closures::isValid(), array(0)));
    $this->assertTrue($this->runClosure(Closures::isValid(), new \stdClass()));

    $this->assertFalse($this->runClosure(Closures::isValid(), 0));
    $this->assertTrue($this->runClosure(Closures::isValid(false), 0));
  }

  /**
   * @test
   * @group Closures
   * @group Closures.matchRegex
   * @covers STJ\Database\Dbo\Closures
   */
  public function matchRegex() {
    $this->assertFalse($this->runClosure(Closures::matchRegex('/test[1-3]/'), 'test4'));
    $this->assertTrue($this->runClosure(Closures::matchRegex('/test[1-3]/'), 'test1'));
  }

  /**
   * @test
   * @group Closures
   * @group Closures.inArray
   * @group Closures.allInArray
   * @covers STJ\Database\Dbo\Closures
   */
  public function inArray() {
    $this->assertFalse($this->runClosure(Closures::inArray(array(1,2,3,4)), 5));
    $this->assertTrue($this->runClosure(Closures::inArray(array(1,2,3,4)), 4));

    $this->assertFalse($this->runClosure(Closures::allInArray(array(1,2,3,4)), array(2,3,4,5)));
    $this->assertFalse($this->runClosure(Closures::allInArray(array(1,2,3,4)), 5));
    $this->assertTrue($this->runClosure(Closures::allInArray(array(1,2,3,4)), array(3,2)));
  }

  /**
   * @test
   * @group Closures
   * @group Closures.stringWithinLength
   * @covers STJ\Database\Dbo\Closures
   */
  public function stringWithinLength() {
    $this->assertFalse($this->runClosure(Closures::stringWithinLength(5), '123456'));
    $this->assertTrue($this->runClosure(Closures::stringWithinLength(5), '12345'));
    $this->assertTrue($this->runClosure(Closures::stringWithinLength(5), '1234'));
  }
}