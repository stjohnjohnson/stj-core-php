<?php

namespace STJ\Database\Dbo;

use Exception,
    PDO,
    ReflectionMethod;

/**
 * Row Based Object
 *
 * Represents a database row as an actual object
 *
 * @see https://github.com/stjohnjohnson/stj-core-php
 */
abstract class RowBased extends Metadriven {
  protected static $_belongs_to = array();
  protected static $_has_a = array();
  protected static $_has_many = array();
  protected static $_has_many_through = array();

  /**
   * Loads data from the database into the current object based on the current
   * values in the primary keys
   *
   * @param bool $forceRW
   *   Force RW connector
   * @param bool $safe
   *   Don't throw exception on failure
   */
  protected function _performLoad($forceRW = false, $safe = false) {
    $base_class = get_class($this);

    // Generate the sql to populate the object
    $sql = static::_getSQLSelect();

    // Add the Where
    $sql .= " WHERE ";

    // Get all unique keys
    $keys = static::_getMetaKeys($base_class);
    if (empty($keys)) {
      throw new RowBasedException('No unique criteria to load from');
    }

    // Loop through Primary and Unique keys (try to find something)
    foreach ($keys as $unique) {
      $lookup = array_flip($unique);
      $good = false;
      foreach ($lookup as $key => $value) {
        if (isset($this->$key)) {
          $good = true;
        }
        $lookup[$key] = $this->get($key);
      }

      if ($good) {
        break;
      }
    }

    $params = array();
    $where = $this->_getSQLWhereIn($lookup, $params);

    // Complete statement
    $sql .= $where;

    // Use RW if requested, otherwise default to RO
    if ($forceRW) {
      $stmt = static::getDbRW()->prepare($sql);
    } else {
      $stmt = static::getDbRO()->prepare($sql);
    }

    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
      if ($safe) {
        return;
      } else {
        throw new RowBasedException('No Record(s) Found', 404);
      }
    }

    $output = static::_processLoadResults($rows);

    $this->setFromArray(reset($output));

    // Clean up the class and mark as no-longer new
    $this->_migrateDirtyToClean();
    $this->markAsNew(false);
  }

  /**
   * Converts SQL return into array for importing
   *
   * @param array $rows
   *   Output of SQL FetchAll
   * @return array
   */
  protected static function _processLoadResults(array $rows) {
    // Get primary keys and tablename for class
    $base_class = get_called_class();
    $base_keys = static::_getMetaKeys($base_class, true);
    $base_table = static::_getMetaTable($base_class);

    // Eventual return of objects
    $objects = array();

    // Loop through the rows
    foreach ($rows as $row) {
      $tables = array();

      // Loop through and break into tables
      foreach ($row as $column => $value) {
        // Returns as table.field
        list($table, $field) = explode('.', $column);

        // Move into nice tables
        $tables[$table][$field] = $value;
      }

      // Generate the base primary index
      $primary = array();
      foreach ($base_keys as $key) {
        $primary[] = $tables[$base_table][$key];
      }
      $primary = implode(':', $primary);

      // Create object tracker
      if (!isset($objects[$primary])) {
        $objects[$primary] = array();
      }

      // Loop through table data
      foreach ($tables as $table => $fields) {
        // Get class
        $class = static::_getMetaClass($table);
        // Get primary keys
        $keys = static::_getMetaKeys($class, true);

        // Generate the unique index
        $index = array();
        foreach ($keys as $key) {
          $index[] = $fields[$key];
        }
        $index = implode(':', $index);

        // Fields are empty
        if (trim($index, ':') === '') {
          // Skip this entry
          continue;
        }

        // Create array
        if (!isset($objects[$primary][$class])) {
          $objects[$primary][$class] = array();
        }
        // Create object
        if (!isset($objects[$primary][$class][$index])) {
          // Convert fields
          foreach ($fields as $field => $value) {
            // Get type
            $type = static::_getPropertyType($field, $class);
            // Convert to PHP value
            $fields[$field] = static::_convertFromDBFormat($type, $value);
          }
          // Store as object
          $objects[$primary][$class][$index] = (object) $fields;
        }
      }
    }

    // Loop through objects and store them
    $merged = array_merge(static::$_has_many, array_keys(static::$_has_many_through),
              array_values(static::$_has_many_through));
    foreach ($objects as $index => $classes) {
      $output = array();
      // Loop through classes and figure out what to do with them
      foreach ($classes as $class => $object) {
        // Has many is an array of objects, while belongs to is just an object
        if (in_array($class, $merged)) {
          // Store as array
          $object = $object;
          // Pluralize (poor-man-method) the class name
          $class .= 's';
        } else {
          // Store as object
          $object = reset($object);
        }

        // If this is this object, store internally
        if ($class === static::_removeNamespace($base_class)) {
          $output = array_merge($output, get_object_vars($object));
        } else {
          $output[$class] = $object;
        }
      }

      // Return array
      $objects[$index] = $output;
    }

    // Move has_many_through references
    foreach ($objects as $index => $object) {
      // Loop through until we find an object AND it's in the has_many
      foreach ($object as $field => $value) {
        // Remove 's' from field name
        $class = substr($field, 0, -1);
        // If value is an array & a through value
        if (is_array($value) && in_array($class, array_values(static::$_has_many_through))) {
          $value = static::_rejoinHasManyThrough($class, $value, $object);
        }
      }
    }

    return $objects;
  }

  /**
   * Recombines child relations of has-many-through
   *
   * @param string $class
   *   Class Name of HasManyThrough
   * @param array $array
   *   Array of HasManyThrough
   * @param array $fields
   *   All fields in this object
   * @return array
   */
  private static function _rejoinHasManyThrough($class, array $array, array $fields) {
    // Find all classes that are connected
    $classes = array();
    foreach (static::$_has_many_through as $from => $through) {
      // Look for classes that leverage this one
      if ($class === $through) {
        $classes[] = $from;
      }
    }

    // Loop over each of the joined values
    foreach ($array as $index => $instance) {
      // Loop through classes
      foreach ($classes as $from) {
        // Lookup Keys for that class
        $keys = static::_getMetaKeys($from, true);
        $primary = array();
        foreach ($keys as $key) {
          $primary[] = $instance->$key;
        }
        $primary = implode(':', $primary);

        // If exists in objects, copy it over
        if (isset($fields[$from.'s']) && isset($fields[$from.'s'][$primary])) {
          // Oh god.
          $array[$index]->$from = $fields[$from.'s'][$primary];
        }
      }
    }

    return $array;
  }

  /**
   * Store this object into the database
   */
  protected function _performCreate() {
    // Generate the sql to populate the object
    $params = array();
    $sql = $this->_getSQLInsert($params);

    // Run the command :)
    $stmt = static::getDbRW()->prepare($sql);
    $stmt->execute($params);

    // If we have an auto-increment ID, store it
    $auto = static::_getMetaAuto(get_class($this));
    if ($auto !== false) {
      $this->set($auto, $this->getDbRW()->lastInsertId());
    }

    // Clean up the class and mark as no-longer new
    $this->_migrateDirtyToClean();
    $this->markAsNew(false);
  }

  /**
   * Update the database with modified fields
   *
   * @param bool
   *   Was updated
   */
  protected function _performUpdate() {
    // Prevent updating unchanged objects
    if (count($this->getChangedProperties()) === 0) {
      return false;
    }

    // Generate the sql to update the object
    $params = array();
    $sql = $this->_getSQLUpdate($params);

    // Add the Where
    $sql .= " WHERE ";

    // Get all unique keys
    $keys = static::_getMetaKeys(get_class($this));
    if (empty($keys)) {
      throw new RowBasedException('No unique criteria to load from');
    }

    // Use primary keys
    $keys = array_flip(reset($keys));

    // Populate keys
    foreach ($keys as $key => $value) {
      $keys[$key] = $this->get($key);
    }

    // Generate Where clause
    $where = $this->_getSQLWhereIn($keys, $params);

    // Complete statement
    $sql .= $where . ' LIMIT 1';

    // Execute
    $stmt = static::getDbRW()->prepare($sql);
    $stmt->execute($params);

    // Cleanup fields
    $this->_migrateDirtyToClean();

    return true;
  }

  /**
   * Delete the row from the database
   */
  protected function _performDelete() {
    // Get all unique keys
    $keys = static::_getMetaKeys(get_class($this));
    if (empty($keys)) {
      throw new RowBasedException('No unique criteria to load from');
    }

    // Use primary keys
    $keys = array_flip(reset($keys));

    // Populate keys
    foreach ($keys as $key => $value) {
      $keys[$key] = $this->get($key);
    }

    // Delete the row
    static::deleteMany($keys, 1);

    // Mark row as new
    $this->markAsNew(true);
  }

  /**
   * Delete many of this object
   *
   * @param array $fields
   *   Hash of fields => values
   * @param int $limit
   *   Limit on deleting
   */
  public static function deleteMany(array $fields, $limit = 0) {
    // Create instance of the class
    $class = get_called_class();
    $obj = new $class();

    // Generate the sql to delete the object
    $params = array();
    $table = static::_getMetaTable($class);
    $sql = "DELETE FROM `$table` WHERE";

    // Generate Where clause (ehh, things I gotta do)
    $where = static::_getSQLWhereIn($fields, $params);

    // Complete statement
    $sql .= $where;

    // Add limit
    if ($limit > 0) {
      $sql .= " LIMIT $limit";
    }

    // Execute
    $stmt = static::getDbRW()->prepare($sql);
    $stmt->execute($params);
  }

  /**
   * Delete many of this object
   *
   * @param array $fields
   *   Hash of fields => values
   * @param bool $forceRW
   *   Use the RW connector
   * @param int $limit
   *   Limit on deleting
   * @return array
   *   Objects returned from DB
   */
  public static function loadMany(array $fields = array(), $forceRW = false, $limit = 0) {
    // Create instance of the class
    $baseclass = get_called_class();
    $obj = new $baseclass();

    // Generate the sql to populate the object
    $sql = static::_getSQLSelect();

    // Generate Where clause (ehh, things I gotta do)
    $params = array();
    $where = static::_getSQLWhereIn($fields, $params);

    // Add the Where
    if (!empty($where)) {
      $sql .= " WHERE " . $where;
    }

    // Add limit
    if ($limit > 0) {
      $sql .= " LIMIT $limit";
    }

    // Use RW if requested, otherwise default to RO
    if ($forceRW) {
      $stmt = static::getDbRW()->prepare($sql);
    } else {
      $stmt = static::getDbRO()->prepare($sql);
    }

    // Get results
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert rows into arrays/objects
    $objects = static::_processLoadResults($rows);
    $result = array();

    // Loop through results
    foreach ($objects as $object) {
      $instance = new $baseclass();

      // @todo call BeforeLoad

      $instance->setFromArray($object);

      // Clean up the class
      $method = new ReflectionMethod($instance, '_migrateDirtyToClean');
      $method->setAccessible(true);
      $method->invoke($instance);

      // Mark as no-longer new
      $instance->markAsNew(false);

      // @todo call AfterLoad

      $result[] = $instance;
    }

    return $result;
  }

  /**
   * Generates the SQL Select Statement for this class
   *
   * @return string SelectSQL
   */
  protected static function _getSQLSelect() {
    // Generate Select Columns
    $sql = 'SELECT ';
    $base_class = get_called_class();
    $base_table = static::_getMetaTable($base_class);

    // Get fields for the following classes
    $classes = array($base_class);
    // Include relations
    $classes = array_merge($classes, static::$_belongs_to,
               static::$_has_a, static::$_has_many);
    // Include has_many_through
    $classes = array_merge($classes, array_keys(static::$_has_many_through),
               array_values(static::$_has_many_through));

    // Loop through classes
    $columns = array();
    foreach (array_unique($classes) as $class) {
      $fields = array_keys(static::_getMetaFields($class));
      $table = static::_getMetaTable($class);

      // Loop through and create nice fields
      foreach ($fields as $i => $field) {
        $columns[] = "`$table`.`$field`";
      }
    }

    $sql .= implode(', ', $columns);

    // Add From
    $sql .= ' FROM `' . $base_table . '`';

    // Loop through belongs to
    // A belongs_to B via key from B
    foreach (static::$_belongs_to as $class) {
      $table_B = static::_getMetaTable($class);
      $keys_B = static::_getMetaKeys($class, true);

      $sql .= " LEFT JOIN `$table_B` USING (`" . implode('`,`', $keys_B) . "`)";
    }

    // Loop through has_a and has_many
    // A has_many B via key from A
    foreach (array_merge(static::$_has_a, static::$_has_many) as $class) {
      $table_B = static::_getMetaTable($class);
      $keys_A = static::_getMetaKeys($base_class, true);

      $sql .= " LEFT JOIN `$table_B` USING (`" . implode('`,`', $keys_A) . "`)";
    }

    // Loop through has_many_through
    // A has_many B through C via key from A,C
    // @disabled until tested
    foreach (static::$_has_many_through as $class => $through) {
      $table_B = static::_getMetaTable($class);
      $table_C = static::_getMetaTable($through);
      $keys_A = static::_getMetaKeys($base_class, true);
      $keys_B = static::_getMetaKeys($class, true);

      $sql .= " LEFT JOIN `$table_C` USING (`" . implode('`,`', $keys_A) . "`)";
      $sql .= " LEFT JOIN `$table_B` USING (`" . implode('`,`', $keys_B) . "`)";
    }

    return $sql;
  }

  /**
   * Generates the SQL Insert Statement for this class
   *
   * @return string InsertSQL
   */
  protected function _getSQLInsert(array &$params) {
    // Get fields
    $fields = array_keys(static::_getMetaFields(get_class($this)));
    $table = static::_getMetaTable(get_class($this));

    // Generate Select Columns
    $sql = "INSERT INTO `$table` ";

    // Loop through and create nice fields
    $values = array();
    foreach ($fields as $i => $field) {
      $fields[$i] = "`$field`";

      // Only insert if set
      if ($this->hasPropertyChanged($field)) {
        // Convert value based on type
        $type = static::_getPropertyType($field, get_class($this));
        $value = static::_convertToDBFormat($type, $this->get($field));

        // Use binding with
        $params[$field] = $value;
        $values[] = ":$field";
      } else {
        // Use default
        $values[] = 'DEFAULT';
      }
    }
    $sql .= '(' . implode(', ', $fields) . ') VALUES (' . implode(',', $values) . ')';

    return $sql;
  }

  /**
   * Generates the SQL Update Statement for this class
   *
   * @return string UpdateSQL
   */
  protected function _getSQLUpdate(array &$params) {
    // Get fields
    $fields = array_keys(static::_getMetaFields(get_class($this)));
    $table = static::_getMetaTable(get_class($this));

    // Generate Select Columns
    $sql = "UPDATE `$table` SET ";

    // Loop through and create nice fields
    foreach ($fields as $i => $field) {
      // If field is not changing, skip
      if (!$this->hasPropertyChanged($field)) {
        unset($fields[$i]);
        continue;
      }

      // Update the field
      $fields[$i] = "`$field` = ";

      // If field is shifting, get the shift
      if ($this->isPropertyShifting($field)) {
        list($mode, $value) = explode(':', $this->getPropertyShift($field), 2);

        $fields[$i] .= "`$field` $mode ?";
      } else {
        $fields[$i] .= "?";
        $value = $this->get($field);
      }

      // Convert value based on type
      $type = static::_getPropertyType($field, get_class($this));
      $value = static::_convertToDBFormat($type, $value);

      // Use binding
      $params[] = $value;
    }
    $sql .= implode(', ', $fields);

    return $sql;
  }

  /**
   * Generates WHERE clause for specific fields
   *
   * @param array $fields
   *   field => values
   * @param array &$params
   *   Values to bind
   * @return string WhereSQL
   */
  protected static function _getSQLWhereIn(array $fields, array &$params) {
    $sql = array();

    // Get table name
    $class = get_called_class();
    $table = static::_getMetaTable($class);

    foreach ($fields as $field => $values) {
      // Convert non-arrays to arrays
      if (!is_array($values)) {
        $values = array($values);
      }
      $count = count($values);

      // Skip if no values
      if ($count == 0) {
        continue;
      }

      // Detect Operator (default at IN for array and = for single)
      $operator = ($count > 1 ? 'in' : '=');
      if (strpos($field, ':') > 0) {
        list($field, $operator) = explode(':', $field, 2);
      }

      // Convert Values
      $type = static::_getPropertyType($field, $class);
      foreach ($values as $k => $value) {
        $values[$k] = static::_convertToDBFormat($type, $value);
      }

      // Generate SQL
      $field = "`$table`.`$field`";
      $value = '';

      // Convert lt to <
      $operator = strtr($operator, array(
           'eq' => '=',
          'neq' => '!=',
           'lt' => '<',
          'lte' => '<=',
           'gt' => '>',
          'gte' => '>='
      ));
      switch (strtolower($operator)) {
        case 'in':
        case 'not in':
          $value = '(' . implode(',', array_fill(0, $count, '?')) . ')';
            break;

        case 'between':
        case 'not between':
          $value = '? AND ?';
          // just to make sure
          if ($count != 2) {
            throw new RowBasedException("Operator '$operator' requires two arguments");
          }
          break;

        case '=':
        case '!=':
          // Check for NULL
          if (reset($values) === null) {
            $operator = 'is' . ($operator == '!=' ? ' not' : '');
            $value = 'NULL';
            $values = array();
            break;
          }
        case 'like':
        case 'not like':
        case '<':
        case '<=':
        case '>':
        case '>=':
        case '<=>':
        case '<>':
          $value = '?';

          // Validate length
          if ($count != 1) {
            throw new RowBasedException("Operator '$operator' supports only one argument");
          }
          break;

        default:
          throw new RowBasedException("Unknown Operator '$operator'");
      }

      $sql[] = $field . ' ' . strtoupper($operator) . ' ' . $value;
      $params = array_merge($params, $values);
    }

    return implode(' AND ', $sql);
  }

  /**
   * Load a row from this RowBased by Primary Key
   *
   * @param int $id
   *   Primary Key value
   * @return RowBased
   *   Loaded Object
   * @throws RowBasedException on invalid primary keys
   */
  public static function loadByID($id) {
    // Create instance of the class
    $class = get_called_class();

    // Load Key
    $keys = static::_getMetaKeys($class, true);
    if (count($keys) !== 1) {
      throw new RowBasedException('Unable to Load By ID for Multiple Column Primary Keys');
    }

    // Create Object
    $obj = new $class(array_combine($keys, array($id)));

    // Now load
    return $obj->load();
  }
}

class RowBasedException extends Exception {}
