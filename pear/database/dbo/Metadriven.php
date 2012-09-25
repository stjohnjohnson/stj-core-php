<?php

namespace STJ\Database\Dbo;

use Exception,
    PDO;

use STJ\Core\Cache;

/**
 * Associates class name to MySQL table
 */
abstract class Metadriven extends Stateful {
    // Metadata about the tables
  protected static $_meta = array();
  // Read-Only Connector
  protected static $_dbRO = null;
  // Read-Write Connector
  protected static $_dbRW = null;

  /**
   * Get the Read-Only PDO connector
   *
   * @return PDO
   */
  public static function getDbRO() {
    if (static::$_dbRO === null) {
      throw new MetadrivenException('No Read-Only PDO Available', 500);
    }
    return static::$_dbRO;
  }

  /**
   * Get the Read-Write PDO connector
   *
   * @return PDO
   */
  public static function getDbRW() {
    if (static::$_dbRW === null) {
      throw new MetadrivenException('No Read-Write PDO Available', 500);
    }
    return static::$_dbRW;
  }

  /**
   * Set the Read-Only PDO connector
   *
   * @param PDO $pdo
   *   Read-Only PDO object
   */
  public static function setDbRO($pdo) {
    static::$_dbRO = $pdo;
  }

  /**
   * Get the Read-Write PDO connector
   *
   * @param PDO $pdo
   *   Read-Write PDO object
   */
  public static function setDbRW($pdo) {
    static::$_dbRW = $pdo;
  }

  /**
   * Is this a trackable property
   *
   * @param string $property
   *   Property name
   * @return boolean
   *   False if not a property
   */
  public function isProperty($property) {
    $fields = static::_getMetaFields(get_class($this));

    // Check if we have that Property
    return isset($fields[$property]);
  }

  /**
   * What are the trackable properties
   *
   * @return array
   *   List of properties
   */
  public function getProperties() {
    $fields = static::_getMetaFields(get_class($this));

    return array_keys($fields);
  }

  /**
   * Get MySQL Datatype of a Property
   *
   * @param string $property
   *   Property name
   * @param string $class
   *   Class name
   * @return string|boolean
   *   Datatype or False if not found
   */
  protected static function _getPropertyType($property, $class) {
    // Load fields
    $fields = static::_getMetaFields($class);

    // Check if we have that Property
    if (isset($fields[$property])) {
      return $fields[$property]['type'];
    } else {
      return false;
    }
  }

  /**
   * Get MySQL Options of an Enum/Set Property
   *
   * @param string $property
   *   Property name
   * @param string $class
   *   Class name
   * @return array
   *   Array of acceptable values
   */
  protected static function _getPropertyOptions($property, $class) {
    $type = self::_getPropertyType($property, $class);

    if (strpos($type, 'enum') === 0 || strpos($type, 'set') === 0) {
      $enum = array();
      preg_match_all("/'(?P<values>[^']*)'/", $type, $enum);
      return $enum['values'];
    }

    return array();
  }

  /**
   * Removes namespace from class
   *
   * @param string $class
   *   Class name
   * @return string
   *   Class without Namespace
   */
  protected static function _removeNamespace($class) {
    $namespaced = explode('\\', $class);
    return end($namespaced);
  }

  /**
   * Loads MetaData about the table from MySQL
   *
   * @param string $class
   *   Class name
   * @return array
   */
  protected static function _getMetaData($class) {
    // Remove the namespace
    $class = self::_removeNamespace($class);

    // Check static caching
    if (isset(static::$_meta[$class])) {
      return static::$_meta[$class];
    }

    // Check if meta already exists in APC cache
    $meta = Cache::get('Model\MetaData_' . $class);
    if ($meta !== false) {
      // Store in static caching
      static::$_meta[$class] = $meta;

      return $meta;
    }

    $meta = array('fields' => array(), 'keys' => array(),
                    'prim' => array(), 'auto' => null);

    // Load Columns
    $stmt = static::getDbRO()->prepare('SELECT c.table_name, c.column_name,
        c.column_type, c.extra, c.is_nullable, c.column_default
      FROM information_schema.columns c
      WHERE c.table_schema = DATABASE() AND c.table_name LIKE ?');

    $stmt->execute(array($class));

    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($columns)) {
      throw new MetadrivenException('Table Not Found: ' . $class, 500);
    }
    foreach ($columns as $column) {
      $meta['table'] = $column['c.table_name'];

      // Check if auto-increment
      if (strpos($column['c.extra'], 'auto_increment') !== false) {
        $meta['auto'] = $column['c.column_name'];
      }

      $meta['fields'][$column['c.column_name']] = array(
          'type' => $column['c.column_type'],
          'null' => ($column['c.is_nullable'] === 'YES'),
       'default' => $column['c.column_default'],
      );
    }

    // Load Primary/Unique Keys
    $stmt = static::getDbRO()->prepare("SELECT kcu.column_name, tc.constraint_type
      FROM information_schema.table_constraints tc
        LEFT JOIN information_schema.key_column_usage kcu
          ON tc.constraint_name = kcu.constraint_name
      WHERE tc.table_schema = DATABASE() AND kcu.table_schema = DATABASE()
        AND tc.table_name = ? AND kcu.table_name = ?
        AND (tc.constraint_type = 'UNIQUE' OR tc.constraint_type = 'PRIMARY KEY')");
    $stmt->execute(array($meta['table'], $meta['table']));

    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
      // Check if primary key
      if (strcmp('PRIMARY KEY', $column['tc.constraint_type']) === 0) {
        $meta['prim'][] = $column['kcu.column_name'];
      }
      // Check for unique keys
      if (strcmp('UNIQUE', $column['tc.constraint_type']) === 0) {
        $meta['keys'][] = $column['kcu.column_name'];
      }
    }

    // Store keys as array of uniques
    if (!empty($meta['keys'])) {
      $meta['keys'] = array($meta['keys']);
    }

    // Append Primary keys to Unique keys
    if (!empty($meta['prim'])) {
      array_unshift($meta['keys'], $meta['prim']);
    }
    unset($meta['prim']);

    // Store in APC
    Cache::set('Model\MetaData_' . $class, $meta);
    // Store in static
    static::$_meta[$class] = $meta;

    return $meta;
  }

  /**
   * Returns the table name for this class
   *
   * @param string $class
   *   Class to get MetaData about
   * @return string
   *   Table name
   */
  protected static function _getMetaTable($class) {
    $meta = static::_getMetaData($class);
    return $meta['table'];
  }

  /**
   * Returns the class name for this table
   *  - Only works for previously loaded classes
   *
   * @param string $table
   *   Table Name
   * @return string|bool
   *   Table name or false on failure
   */
  protected static function _getMetaClass($table) {
    // Loop through meta looking for tables that match
    foreach (self::$_meta as $class => $meta) {
      if ($meta['table'] === $table) {
        return $class;
      }
    }

    return false;
  }

  /**
   * Returns the list of fields for this class
   *
   * @param string $class
   *   Class to get MetaData about
   * @return array
   *   Hash of Field => Details
   */
  protected static function _getMetaFields($class) {
    $meta = static::_getMetaData($class);
    return $meta['fields'];
  }

  /**
   * Returns the list of primary keys for this class
   *
   * @param string $class
   *   Class to get MetaData about
   * @param bool $main
   *   Return only the main key
   * @return array
   *   Array of unique keys
   */
  public static function _getMetaKeys($class, $main = false) {
    $meta = static::_getMetaData($class);
    $keys = $meta['keys'];

    // Return only the first key
    if ($main && !empty($keys)) {
      $keys = reset($keys);
    }

    return $keys;
  }

  /**
   * Returns the auto-increment field
   *
   * @param string $class
   *   Class to get MetaData about
   * @return string|null
   *   Field if there is an Auto-Inc, Null otherwise
   */
  public static function _getMetaAuto($class) {
    $meta = static::_getMetaData($class);
    return $meta['auto'];
  }

  /**
   * Converts internal values from DB format
   *
   * @param string $type
   *   MySQL Type
   * @param string $value
   *   Value from MySQL engine
   * @return varied
   *   PHP value
   */
  protected static function _convertFromDBFormat($type, $value) {
    // Skip empty fields
    if ($value == null) {
      return $value;
    }

    // Convert MySQL to unix time
    if (strpos($type, 'timestamp') === 0 && !is_numeric($value)) {
      $value = strtotime($value);
      // Invalid dates get stored as 0
      if ($value === false || $value === -62169984000) {
        $value = 0;
      }
    }

    // Convert set from comma separated
    if (strpos($type, 'set') === 0 && !empty($value)) {
      $value = explode(',', $value);
    }

    // Convert int
    if (strpos($type, 'int') === 0) {
      $value = (int) $value;
    }

    // Convert float
    if (strpos($type, 'float') === 0) {
      $value = (float) $value;
    }

    // Convert tinyint to boolean
    if (strpos($type, 'tinyint') === 0) {
      $value = (bool) ($value == 1);
    }

    return $value;
  }

  /**
   * Converts internal value into DB format
   *
   * @param string $type
   *   MySQL Type
   * @param varied $value
   *   Value from PHP engine
   * @return string
   *   MySQL value
   */
  protected static function _convertToDBFormat($type, $value) {
    // Skip nulls
    if ($value === null) {
      return $value;
    }

    // Convert unix time to MySQL
    if (strpos($type, 'timestamp') === 0 && is_numeric($value)) {
      // Store 0 as full datetime
      if ($value == 0) {
        return '0000-00-00 00:00:00';
      } else {
        return date('Y-m-d H:i:s', $value);
      }
    }

    // Convert set to comma separated
    if (strpos($type, 'set') === 0) {
      if (is_array($value)) {
        $value = implode(',', $value);
      }
      return $value;
    }

    // Convert tinyint to boolean
    if (strpos($type, 'tinyint') === 0) {
      return ($value ? 1 : 0);
    }

    // Convert integer to string
    if (strpos($type, 'int') === 0 || strpos($type, 'float') === 0) {
      return (string) $value;
    }

    return $value;
  }
}

class MetadrivenException extends Exception {}