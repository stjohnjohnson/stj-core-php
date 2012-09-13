<?php

namespace STJ\Core;

/**
 * HTML Test
 *
 * @see https://github.com/stjohnjohnson/stj-core-php
 */
class HTMLTest extends \UnitTest {
  /**
   * @test
   * @group HTML
   * @group HTML.url
   * @dataProvider providerUrl
   * @covers STJ\Core\HTML
   */
  public function url($value, $base, array $routes = array(), array $args = array(), $fragment = '') {
    $this->assertEquals($value, HTML::url($base, $routes, $args, $fragment));
  }

  /**
   * Data Provider for url()
   *
   * @return array
   */
  public function providerUrl() {
    $cases = array();

    $cases[] = array('/error/404', '/', array('error',404));
    $cases[] = array('/error/404?message=test', '/', array('error',404), array('message' => 'test'));
    $cases[] = array('/error/404?message=test#alert', '/', array('error',404), array('message' => 'test'), 'alert');

    return $cases;
  }

  /**
   * @test
   * @group HTML
   * @group HTML.node
   * @covers STJ\Core\HTML
   */
  public function node() {
    $this->assertEquals('<test attr="value" />',
                        HTML::node('test', array('attr' => 'value')));

    $this->assertEquals('<test attr="value">inner</test>',
                        HTML::node('test', array('attr' => 'value'), 'inner'));
  }

  /**
   * @test
   * @group HTML
   * @group HTML.option
   * @covers STJ\Core\HTML
   */
  public function option() {
    $this->assertEquals('<option value="value">title</option>',
                        HTML::option('value', 'title'));

    $this->assertEquals('<option value="value" selected="selected">title</option>',
                        HTML::option('value', 'title', true));
  }

  /**
   * @test
   * @group HTML
   * @group HTML.select
   * @dataProvider providerSelect
   * @covers STJ\Core\HTML
   */
  public function select($expected, $array, $selected = null, $blank = false, array $additional = array()) {
    $this->assertEquals($expected, HTML::select($array, $selected, $blank, $additional));
  }

  /**
   * Data Provider for select()
   *
   * @return array
   */
  public function providerSelect() {
    $cases = array();

    // Simple Use
    $cases[] = array('<select size="1"><option value="A" title="B">B</option></select>',
                     array(array('value' => 'A', 'title' => 'B')));
    // Key=>Value
    $cases[] = array('<select size="1"><option value="A" title="B">B</option></select>',
                     array('A' => 'B'));
    $cases[] = array('<select size="1"><option value="0" title="value">value</option></select>',
                     array('value'));
    // Case with selected value
    $cases[] = array('<select size="1"><option value="A" title="B" selected="selected">B</option></select>',
                     array(array('value' => 'A', 'title' => 'B')), 'A');
    // Case with blank
    $cases[] = array('<select size="1"><option value=" " title=" "> </option>' . PHP_EOL . '<option value="A" title="B">B</option></select>',
                     array(array('value' => 'A', 'title' => 'B')), null, true);
    // Case with multi-select
    $cases[] = array('<select size="1" multiple="multiple"><option value="A" title="B">B</option></select>',
                     array(array('value' => 'A', 'title' => 'B')), null, false, array('multiple' => 'multiple'));
    // Case with bigger size
    $cases[] = array('<select size="3"><option value="A" title="B">B</option></select>',
                     array(array('value' => 'A', 'title' => 'B')), null, false, array('size' => '3'));
    // Extra Options
    $cases[] = array('<select size="1" class="sample"><option value="A" title="B">B</option></select>',
                     array(array('value' => 'A', 'title' => 'B')), null, false, array('class' => 'sample'));


    return $cases;
  }
}