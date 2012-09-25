<?php

namespace STJ\Database\Dbo;

/**
 * Validation Error Test
 */
class ValidationErrorTest extends \UnitTest {
  /**
   * @test
   * @group ValidationError
   * @group ValidationError.getSet
   * @covers STJ\Database\Dbo\ValidationError::__get
   * @covers STJ\Database\Dbo\ValidationError::__set
   * @covers STJ\Database\Dbo\ValidationError::__isset
   */
  public function getSet() {
    $foo = new ValidationError();

    // Test with a normal value
    $this->assertFalse(isset($foo->test));
    $this->assertEquals(array(), $foo->test);
    $foo->test = 'abc';
    // Gets converted to array
    $this->assertEquals(array('abc'), $foo->test);
    $this->assertTrue(isset($foo->test));

    // Test with an array
    $this->assertFalse(isset($foo->array));
    $this->assertEquals(array(), $foo->array);
    $foo->array = array('abc','def');
    $this->assertEquals(array('abc','def'), $foo->array);
    $this->assertTrue(isset($foo->array));
  }

  /**
   * @test
   * @group ValidationError
   * @group ValidationError.arrayConversion
   * @covers STJ\Database\Dbo\ValidationError::toArray
   * @covers STJ\Database\Dbo\ValidationError::fromArray
   */
  public function arrayConversion() {
    $foo = new ValidationError();
    $array = array(
        'foo' => array('abc'),
        'bar' => array('def'),
        'joe' => array(123,456)
    );

    // Test that it is empty
    $this->assertEmpty($foo->toArray());
    // Import the array
    $foo->fromArray($array);
    // Assert that it is the same
    $this->assertEquals($array, $foo->toArray());
  }

  /**
   * @test
   * @group ValidationError
   * @group ValidationError.merge
   * @covers STJ\Database\Dbo\ValidationError::merge
   */
  public function merge() {
    $foo = new ValidationError();
    $foo->fromArray(array(
        'bar' => array('def'),
        'joe' => array(123,456)
    ));

    $bar = new ValidationError();
    $bar->fromArray(array(
        'bar' => array('hij'),
        'jill' => 15
    ));

    // Merge and validate
    $foo->merge($bar);
    $this->assertEquals(array(
        'bar' => array('def','hij'),
        'joe' => array(123,456),
        'jill' => array(15)
    ), $foo->toArray());
  }

  /**
   * @test
   * @group ValidationError
   * @group ValidationError.tisEmpty
   * @covers STJ\Database\Dbo\ValidationError::isEmpty
   */
  public function tisEmpty() {
    $foo = new ValidationError();
    $this->assertTrue($foo->isEmpty());
    $foo->sample = 'abc';
    $this->assertFalse($foo->isEmpty());
  }

  /**
   * @test
   * @group ValidationError
   * @group ValidationError.toException
   * @covers STJ\Database\Dbo\ValidationError::toException
   */
  public function toException() {
    $foo = new ValidationError();
    $foo->fromArray(array(
        'bar' => array('hij'),
        'jill' => 15
    ));

    $ex = $foo->toException();
    $this->assertEquals("hij\n15", $ex->getMessage());
    $this->assertEquals(array(
        'bar' => array('hij'),
        'jill' => array(15)
    ), $ex->errors);
  }
}