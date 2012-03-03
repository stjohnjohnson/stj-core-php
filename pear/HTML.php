<?php

namespace stj;

/**
 * Functions to assist in dynamically building HTML
 *
 * @see https://github.com/stjohnjohnson/stj-core-php
 */
class HTML {
  /**
   * Generates URL like 'controller/action/?param1=value&...\#frag'
   *
   * @param string $base
   *   Base url: yahoo.com/
   * @param array $routes
   *   Additional routes: route/route/route
   * @param array $args
   *   GET arguments ?key=>value
   * @param string $fragment
   *   Hash tag
   * @return string
   */
  public function url($base = '/', array $routes = array(), array $args = array(), $fragment = '') {
    $url = rtrim($base, '/') . '/' . implode('/', $routes);

    if (!empty($args)) {
      $url .= '?' . http_build_query($args);
    }

    if (!empty($fragment)) {
      $url .= "#$fragment";
    }

    return $url;
  }

  /**
   * Generates an HTML node
   *
   * @param string $tag
   *   Name of tag
   * @param array $attributes
   *   Array of key => value attributes to put in the node
   * @param string $innerHTML
   *   Text inside the node
   * @return string
   */
  public static function node($tag, array $attributes, $innerHTML = null) {
    $output = '<' . $tag;

    foreach ($attributes as $attr => $value) {
      $output .= ' ' . $attr . '="' . htmlspecialchars($value) . '"';
    }

    if (!isset($innerHTML)) {
      $output .= ' />';
    } else {
      $output .= '>' . $innerHTML . '</' . $tag . '>';
    }

    return $output;
  }

  /**
   * Generates an HTML select tag with options
   *
   * @param array $array
   *   Option array with value => title
   * @param string $selected
   *   Value that is currently selected
   * @param bool $blank
   *   Is there a blank one in the beginning
   * @param array $additional
   *   Attributes to apply to the select box
   * @return string
   */
  public static function select(array $array, $selected = null, $blank = false, array $additional = array()) {
    // Create attribute array
    $attr = array('size' => 1);

    // Merge with param
    $attr = array_merge($attr, $additional);

    // Add blank item to the top
    if ($blank) {
      $array = array_merge(array(' ' => ' '), $array);
    }

    // Generate the option HTML first
    $options = array();
    foreach ($array as $value => $obj) {
      $attributes = array(
          'value' => $value
      );

      // Array
      if (is_array($obj)) {
        $attributes = array_merge($attributes, $obj);
      } else {
        $attributes['title'] = $obj;
      }

      // Is Selected
      if ($selected === $attributes['value']) {
        $attributes['selected'] = 'selected';
      }

      // Default to blank title
      $title = '';
      if (isset($attributes['title'])) {
        $title = $attributes['title'];
      }

      $options[] = self::node('option', $attributes, htmlspecialchars($title));
    }

    // Build the select node with all the data
    return self::node('select', $attr, implode(PHP_EOL, $options));
  }

  /**
   * Generate an HTML option tag
   *
   * @param string $value Value of the element
   * @param string $text Text inside the element
   * @param bool $selected Is selected
   * @param array $additional Attributes to apply to the option element
   * @return type
   */
  public static function option($value, $text, $selected = false, array $additional = array()) {
    $attr = array_merge($additional, array('value' => $value));

    if ($selected) {
      $attr['selected'] = 'selected';
    }

    return self::node('option', $attr, htmlspecialchars($text));
  }
}