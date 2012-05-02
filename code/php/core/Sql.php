<?php
/*
 * (c) 2012 Damien Corpataux
 *
 * Licensed under the GNU GPL v3.0 license,
 * accessible at http://www.gnu.org/licenses/gpl-3.0.html
 *
**/

class xSqlFactory {

    public static $driver = 'mysql';

    /**
     * Prevents factory instanciation.
     * @protected
     */
    protected function __construct() {}

    /**
     * Returns classname (driver implementation) from type, according xSqlFactory::$driver.
     * @param string xSql type (eg. select, where, ...)
     * @return mixed Driver implementation class name.
     */
    protected static function get_class($type) {
        $driver = self::$driver;
        $class = "xSql{$type}{$driver}";
        if (!class_exists($class)) throw new Exception("Cannot create '{$type}': class {$class} does not exist.");
        return $class;
    }

    /**
     * Returns a driver implementation class instance according the given $type.
     * @param string xSql class to create (eg: 'select', 'where', ...)
     * @param mixed Constructor argument 1
     * @param mixed Constructor argument ...
     * @param mixed Constructor argument n
     * @param ...
     */
    public static function create($type) {
        $class = self::get_class($type);
        // Determines function arguments (2..n)
        // TODO: manage args passed by reference: http://stackoverflow.com/questions/1899299/phpoop-how-to-call-class-constructor-manually/1906425#1906425
        $arguments = array_slice(func_get_args(), 1);
        // Calls class constructor with args
        $refClass = new ReflectionClass($class);
        return $refClass->newInstanceArgs($arguments);
    }

    /**
     * Returns the value the given static $property.
     * @param string Property name.
     * @return mixed Property value.
     */
    public static function get_static($type, $property) {
        $class = self::get_class($type);
        return $class::$$property;
    }

}

/**
 * Base class for SQL elements.
 * @package xSql
 */
abstract class xSqlElement {

    /**
     * Creates and returns an instance of the underlying driver class.
     * Eg. xSqlSelect::create() will return an instance of xSqlSelectMysql class
     * if xSqlFactory::$driver is set to 'mysql'.
     * @param mixed Constructor argument 1
     * @param mixed Constructor argument ...
     * @param mixed Constructor argument n
     * @param ...
     */
    public static function create() {
        $classname = get_called_class();
        $type = str_replace('xSql', null, $classname);
        $arguments = func_get_args();
        array_unshift($arguments, $type);
        return call_user_func_array(
            array('xSqlFactory', 'create'),
            $arguments
        );
    }

    /**
     * Returns the value the given static $property.
     * @param string Property name.
     * @return mixed Property value.
     */
    public static function get_static($property) {
        $classname = get_called_class().xSqlFactory::$driver;
        return $classname::$$property;
    }

    abstract function __toString();

}

/**
 * Represents a database type.
 * Responsible for: value escaping and enquoting.
 * @package xSql
 */
abstract class xSqlType extends xSqlElement {
    /**
     * Name of the type.
     * @var string
     */
    public $name;

    /**
     * Aliases names for the type.
     * @var string
     */
    public $aliases = array();

    public function format($value) {
        return $this->enquote($this->escape($value));
    }

    /**
     * Returns escaped value.
     * @param mixed Value to escape.
     * @return string Escaped value.
     */
    abstract public function escape($value);

    /**
     * Returns enquoted value.
     * @param mixed Value to enquote.
     * @return string Enquoted value.
     */
    abstract public function enquote($value);
}
class xSqlTypeDefault extends xSqlType {

    public $name = 'TEXT';

    public $aliases = array();

    public function escape($value) {
        // TODO
        return $value;
    }

    public function enquote($value) {
        return "'{$value}'";
    }

    function __toString() {
        return $this->name;
    }
}

/**
 * Represents an database table.
 * Responsible for: ...
 * @package xSql
 */
class xSqlTable extends xSqlElement {
    /**
     * Name of the table.
     * @var string
     */
    public $name;

    /**
     * Alias.
     * @var string
     */
    public $alias;

    /**
     * Constructor.
     * @var string Table name.
     * @var string Table alias.
     */
    function __construct($name, $alias=null) {
        $this->name = $name;
        $this->alias = $alias;
    }

    function enquote($value) {
        return strlen($value) ? "`{$value}`" : null;
    }

    function __toString() {
        $name = $this->enquote($this->name);
        $alias = $this->enquote($this->alias);
        // Filters empty values
        $parts = array_filter(array($name, $alias));
        // Returns a "{table}[ {alias}]" formatted string
        return implode(' ', $parts);
    }
}

/**
 * Represents an SQL field.
 * Responsible for: linking a field and its type.
 * @package xSql
 */
class xSqlField extends xSqlElement {

    /**
     * Name of the field.
     * @var string
     */
    public $name;

    /**
     * Table for the field.
     * @var xSqlTable
     */
    public $table;

    /**
     * Type of the field.
     * @var xSqlType
     */
    public $type;

    /**
     * Alias for the field.
     * @var string
     */
    public $alias;

    function __construct($name, xSqlTable $table=null, xSqlType $type=null, $alias=null) {
        $this->name = $name;
        $this->alias = $alias;
        if ($type) $this->type = $type;
        else $this->type = xSqlFactory::create('TypeDefault');
        if ($table) $this->table = $table;
        if ($alias) $this->alias = $alias;
    }

    function enquote($value) {
        return strlen($value) ? "`{$value}`" : null;
    }

    function __toString() {
        $name = $this->enquote($this->name);
        $alias = $this->enquote($this->alias);
        // Returns a "{field}[ AS {alias}]" formatted string
        $parts = array_filter(array($name, $alias));
        $field = implode(' AS ', $parts);
        // Returns a "[{table}.]{field}[ AS {alias}]" formatted string
        $parts = array_filter(array($this->table, $field));
        return implode('.', $parts);
    }
}

/**
 * Represents an SQL field.
 * Responsible for: ...
 * FIXME: TODO
 * @todo
 * @package xSql
 */
abstract class xSqlExpression extends xSqlElement {}

/**
 * Represents an abstract SQL clause.
 * @package xSql
 */
abstract class xSqlClause extends xSqlElement {}

/**
 * Represents an SQL statement.
 * Responsible for: containing SQL clauses, outputting an whole SQL statement.
 * @package xSql
 */
class xSql extends xSqlElement {

    public $clauses = array();

    public $order = array('xSqlSelect', 'xSqlFrom', 'xSqlJoins', 'xSqlWhere', 'xSqlGroup', 'xSqlOrder', 'xSqlOffset');

    function __construct($clauses=array()) {
        // Ensures components are allowed
        foreach ($clauses as $clause) {
            if (!$this->is_a($clause, $this->order)) {
                $class = @get_class($clause);
                $class = $class ? $class : '(not an object)';
                throw new Exception("Unsupported clause class type: {$class}");
            }
        }
        // Assigns validated clauses
        $this->clauses = $clauses;
    }

    /**
     * Determines if the given class $name is a child of one of the given $classes.
     * @param object
     * @param array
     * @return boolean
     */
    function is_a($class, array $classes) {
        foreach ($classes as $test_class) if (is_a($class, $test_class)) return true;
        return false;
    }

    function __toString() {
        // Checks components validity (TODO)
        // (eg. xSqlSelect cannot have duplicates)
        null;
        // Orders components according their type (class name)
        $me = $this;
        $sorter = function($a, $b) use ($me) {
            return array_search(get_class($a), $me->order) >= array_search(get_class($b), $me->order);
        };
        usort($this->clauses, $sorter);
        // Generates SQL
        return implode("\n", $this->clauses);
    }
}

/**
 * Represents a SELECT clause.
 * @package xSql
 */
class xSqlSelect extends xSqlClause {

    /**
     * Fields.
     * @var array of xSqlField
     */
    public $fields = array();

    function __construct(array $fields=array()) {
        $this->fields = $fields;
    }

    function __toString() {
        return implode(' ', array("SELECT", implode(', ', $this->fields)));
    }
}

/**
 * Represents a FROM clause.
 * @package xSql
 */
class xSqlFrom extends xSqlClause {

    /**
     * Tables.
     * @var array of xSqlTable
     */
    public $tables = array();

    function __construct($tables=array()) {
        $this->tables = $tables;
    }

    function __toString() {
        return implode(' ', array("FROM", implode(', ', $this->tables)));
    }
}

/**
 * Represents multiple JOIN clauses.
 * @see xSqlJoin
 * @package xSql
 */
class xSqlJoins extends xSqlClause {

    public $joins = array();

    function __construct(array $joins) {
        $this->joins = $joins;
    }

    function __toString() {
        return implode(' ', $this->joins);
    }

}

/**
 * Represents a JOIN clause.
 * @package xSql
 */
class xSqlJoin extends xSqlClause {

    /**
     * @var xSqlField
     */
    public $field_local;

    /**
     * @var xSqlField
     */
    public $field_foreign;

    public $operator = '=';

    // TODO: Factorize xSqlWherePredicate::$operators
    //       and xSqlJoin::$operators
    //       into an xSqlOperator class.
    public $operators = array('=', '...');

    public $type = array('LEFT', 'JOIN');

    /**
     * Allowed join types.
     * Also specifies order when multiple types are used.
     */
    public $types = array('JOIN', 'STRAIGHT_JOIN', 'LEFT', 'RIGHT', 'NATURAL', 'INNER', 'OUTER', 'CROSS');

    function __construct(xSqlField $field_local, xSqlField $field_foreign, array $type=null, $operator=null) {
        $this->field_local = $field_local;
        $this->field_foreign = $field_foreign;
        if ($type) $this->type = $type;
        if ($operator) $this->type = $operator;
    }


    function __toString() {
        // Ensures that types are allowed
        if ($disallowed = array_diff($this->type, $this->types)) {
            die("Type(s) not allowed: ".implode(', ', $disallowed).'.');
        }
        // Orders join types
        $types = $this->type;
        $me = $this;
        usort($types, function($a, $b) use ($me) {
            return array_search($a, $me->types) > array_search($b, $me->types);
        });
        // Generates SQL JOIN
        return implode(' ', array(
            implode(' ', $types),
            $this->field_foreign->table,
            'ON',
            '(',
            $this->field_local,
            $this->operator,
            $this->field_foreign,
            ')'
        ));
    }
}

/**
 * Represents an ORDER BY clause.
 * @package xSql
 */
class xSqlOrder extends xSqlClause {

    /**
     * @var array of xSqlField
     * <code>
     * array(
     *     // Default ASC direction
     *     new xSQLField('fieldname1', 'table1'),
     *     // Explicitely specify direction
     *     array(
     *         'DESC' => new xSQLField('fieldname2', 'table2')
     *     ),
     *     array(
     *         'ASC' => new xSQLField('fieldname3', 'table3')
     *     ),
     * )
     * </code>
     */
    public $fields = array();

    public $directions = array('ASC', 'DESC');

    function __construct(array $fields=array()) {
        $this->fields = $fields;
    }

    function __toString() {
        $parts = array();
        foreach ($this->fields as $item) {
            // Manages item description flavour
            if (is_array($item)) {
                // Flavour array('DESC' => xSqlField)
                $field = array_shift(array_values($item));
                $direction = array_shift(array_keys($item));
            } else {
                // Flavour xSqlField (default direction)
                $field = $item;
                $direction = array_shift(array_values($this->direction));
            }
            // Ensures that types are allowed
            if (!in_array($direction, $this->directions)) {
                die("Direction not allowed for field '{$field->name}': {$direction}");
            }
            $parts[] = "{$field} {$direction}";
        }
        return 'ORDER BY '.implode(', ', $parts);
    }
}

/**
 * Represents a GROUP BY clause.
 * @package xSql
 */
class xSqlGroup extends xSqlClause {

    /**
     * @var array of xSqlField
     * <code>
     * array(
     *     new xSQLField('fieldname1'),
     *     new xSQLField('fieldname2')
     * )
     * </code>
     */
    public $fields = array();

    function __construct(array $fields=array()) {
        $this->fields = $fields;
    }

    function __toString() {
        // FIXME: Manage extra clauses, such as 'WITH ROLLUP' on MySQL.
        return 'GROUP BY '.implode(', ', $this->fields);
    }
}

/**
 * Represents a LIMIT/OFFSET clause(s).
 * @package xSql
 */
class xSqlOffset extends xSqlClause {

    /**
     * @var integer
     * <code>
     * array(5,10)
     * </code>
     */
    public $offset;

    public $limit;

    function __construct($offset, $limit) {
        $this->offset = $offset;
        $this->limit = $limit;
    }

    function __toString() {
        $offset = $this->offset;
        $limit = $this->limit;
        if (!$offset && !$limit) return null;
        elseif ($offset && !$limit) return "OFFSET {$offset}";
        elseif (!$offset && $limit) return "LIMIT {$limit}";
        return "LIMIT {$offset},{$limit}";
    }
}

/**
 * Represents a WHERE clause.
 * @package xSql
 */
class xSqlWhere extends xSqlClause {

    /**
     * @var xSqlPredicateGroup
     */
    public $component;

    function __construct(xSqlWherePredicateGroup $component) {
        $this->component = $component;
    }

    function __toString() {
        return implode(' ', array("WHERE", $this->component));
    }
}

/**
 * Represents a WHERE predicate group.
 * Anatomically, a predicate group contains a set of predicates and an operator.
 * @package xSql
 */
class xSqlWherePredicateGroup extends xSqlElement {

    /**
     * Predicates.
     * @var array Array of xSqlWherePredicate
     */
    public $predicates = array();

    /**
     * Default operator.
     * @var string
     */
    public $operator = 'AND';

    /**
     * Accepted operators.
     * @var array
     */
    public static $operators = array('AND', 'OR');

    function __construct(array $predicates=array(), $operator=null) {
        $this->predicates = is_array($predicates) ? $predicates : array($predicates);
        if ($operator) $this->operator = $operator;
    }

    function __toString() {
        $operator = " {$this->operator} ";
        return implode(array('(', implode($operator, $this->predicates), ')'));
    }
}

/**
 * Represents an atomic WHERE predicate group.
 * Anatomically, a predicate contains a field/value pair and a comparator.
 * @package xSql
 */
class xSqlWherePredicate extends xSqlElement {

    /**
     * Field.
     * @var xSqlField
     */
    public $field;

    /**
     * Field value.
     * @var mixed
     */
    public $value;

    /**
     * Default operator.
     * @var string
     */
    public $comparator = '=';

    /**
     * Accepted comparators.
     * @var array
     */
    public static $comparators = array('=', '!=', '<', '>', '<=', '>=', 'LIKE', 'IN', 'BETWEEN');

    function __construct(xSqlField $field, $value, $comparator=null) {
        $this->field = $field;
        $this->value = $value;
        if ($comparator) $this->comparator = $comparator;
    }

    function __toString() {
        $value = $this->field->type->format($this->value);
        return "{$this->field} {$this->comparator} {$value}";
    }
}