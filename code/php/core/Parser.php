<?php
/*
 * (c) 2012 Damien Corpataux
 *
 * Licensed under the GNU GPL v3.0 license,
 * accessible at http://www.gnu.org/licenses/gpl-3.0.html
 *
**/

/**
 * Parser base class.
 * @package xSql
 */
abstract class xSqlRequestParser {

    public $params = array();

    function __construct(array $params=array()) {
        $this->params = array_merge_recursive($this->params, $params);
    }

    public static function create() {
        // Calls class constructor with args
        $refClass = new ReflectionClass(get_called_class());
        return $refClass->newInstanceArgs(func_get_args());
    }

    /**
     * Parses parameters and returns one or more xSql instance.
     * @return xSql
     * @see $params
     */
    abstract function parse();
}

class xSqlRequestParserSql extends xSqlRequestParser {

    public $types = array('select', 'from', 'joins', 'where', 'group', 'order');//, 'having');

    /**
     * @return xSqlSql Containing the parsed clauses.
     */
    function parse() {
        $c = array();
        foreach ($this->params as $type => $clause) {
            $class = "xSqlRequestParser{$type}";
            if (!class_exists($class))
                throw new Exception("Unsupported clause type: {$type}");
            if (!is_array($clause))
                throw new Exception("Clause description must be an array for clause: {$type}");
            $parser = new $class($clause);
            $c[] = $parser->parse();
        }
        return xSql::create($c);
    }
}

/**
 * Creates a xSqlWhere clause from a structured description array.
 *
 * Example 1 would generate: ((a = 1 AND b = 2 AND (c = 3))
 * REST forms (FYI):
 * - HTTP: ?w[a]=1&w[b]=2&[c]=3 or ?a=1&b=2&c=3
 * - JSON: { a:1, b:2, c:3 }
 * <code>
 * array(
 *     'a' => 1,
 *     'b' => 2,
 *     'c' => 3
 * );
 * </code>
 *
 * Example 2 would generate: ((x = 0) AND ((a = 1 OR b = 2) AND (c = 3)))
 * REST forms (FYI):
 * - HTTP: ?w[0][x]=0&w[1][OR][a]=1&w[1][OR][b]=2&w[1][][c]=3
 * - JSON: [ {x:0}, { OR:{a:1, b:2}, {c:3} } ]
 * <code>
 * array(
 *     array(
 *         'x' => 0
 *     ),
 *     array(
 *         'OR' => array(
 *             'a' => 1,
 *             'b' => 2
 *         ),
 *         array(
 *             'c' => 3
 *         )
 *     )
 * )
 * </code>
 * @package xSql
 */
class xSqlRequestParserWhere extends xSqlRequestParser {

    /**
     * Recursively processes parameter items,
     * extracts structure
     * @param array Base of the where structure to be returned (for recursive calls).
     * @return array A where structure.
     */
    protected function walk_item(array $p) {
        $structure = array();
        // Processes predicates/groups items
        foreach ($p as $key => $item) {
            // Determines wheter to process item:
            // - as a group: which contains other predicates and/or groups
            // - as a predicate: which contains a field:value pair
            if ($this->is_group($key)) {
                // Computes operator, or null if no valid operator is defined
                // TODO: shall invalid operator trigger Exception?
                $operator = array_shift(array_intersect(
                    xSqlWherePredicateGroup::$operators,
                    array($key)
                ));
                // Recurses into group childrens to create predicates array structure
                $predicates = $this->walk_item($item);
                // Adds predicates structure to group
                $structure[] = xSqlWherePredicateGroup::create($predicates, $operator);
            } else /* $item is a predicate */ {
                // Parses comparator
                // TODO: Setup 'comparator' concept and parse comparator
                $comparator = null;
                // In this case, $key => $item is the predicate field:value pair
                $field = xSqlField::create(
                    $key,
                    null,
                    xSqlTypeDefault::create()
                );
                $value = $item;
                // Adds single predicate to group
                $structure[] = xSqlWherePredicate::create($field, $item, $comparator);
            }
        }
        return $structure;
    }

    /**
     * Determines whether the $key implies a group or a predicate.
     * @param mixed Key to be tested.
     * @return bool True if $key represents a group.
     */
    protected function is_group($key) {
        // max() is used to simulate an OR operator
        return max(
            // is $key an operator?
            in_array(strtoupper($key), xSqlWherePredicateGroup::get_static('operators')),
            // is $key an integer index?
            ((int)$key === $key)
        );
    }

    /**
     * @return xSqlWhere Containing the parsed where structure.
     */
    function parse() {
        // Ensures that top-level array is a 'group'
        $p = $this->params;
        if (count($p)) $p = array($p);
        // Recursively creates a predicates structure
        $groups = $this->walk_item($p);
        // Extracts the top-level group object (xSqlWherePredicateGroup)
        $group = array_shift($groups);
        //
        return xSqlWhere::create($group);
    }
}

/**
 * Creates a xSqlSelect clause from a structured description array.
 * @package xSql
 */
class xSqlRequestParserSelect extends xSqlRequestParser {

    /**
     * @return xSqlSelect Containing the parsed fields.
     */
    function parse() {
        $p = $this->params;
        $f = array();
        foreach ($p as $table => $fields) {
            $table = $this->is_assoc($table) ? xSqlFactory::create('Table', $table) : null;
            $fields = is_array($fields) ? $fields : array($fields);
            foreach($fields as $alias => $field) {
                $alias = $this->is_assoc($alias) ? $alias : null;
                $type = xSqlTypeDefault::create(); // FIXME: Implement suitable type creation
                $f[] = xSqlField::create($field, $table, $type, $alias);
            }
        }
        return xSqlSelect::create($f);
    }

    /**
     * Determines whether the $key is an associative array index.
     * @param mixed Key to be tested.
     * @return bool True if $key represents an associative array index.
     */
    protected function is_assoc($key) {
        return !((int)$key === $key);
    }
}

/**
 * Creates a xSqlFrom clause from a structured description array.
 * @package xSql
 */
class xSqlRequestParserFrom extends xSqlRequestParser {

    /**
     * @return xSqlFrom Containing the parsed tables.
     */
    function parse() {
        $p = $this->params;
        $t = array();
        foreach ($p as $alias => $table) {
            $alias = $this->is_assoc($alias) ? $alias : null;
            $t[] = xSqlTable::create($table, $alias);
        }
        return xSqlFrom::create($t);
    }

    /**
     * Determines whether the $key is an associative array index.
     * @param mixed Key to be tested.
     * @return bool True if $key represents an associative array index.
     */
    protected function is_assoc($key) {
        return !((int)$key === $key);
    }
}

/**
 * Creates a xSqlJoins clause from a structured description array.
 * @package xSql
 */
class xSqlRequestParserJoins extends xSqlRequestParser {

    /**
     * @return xSqlJoins Containing the parsed tables.
     */
    function parse() {
        $p = $this->params;
        $j = array();
        foreach ($p as $join) {
            foreach ($join as $type => $joins) {
                $type = $this->is_assoc($type) ? $type : null;
                foreach ($joins as $local => $foreign) {
                    if (!is_array($foreign)) {
                        $j[] = $this->create_join($local, $foreign, $type);
                    } else {
                        foreach ($foreign as $local => $foreign /* ns collision? */) {
                            $j[] = $this->create_join($local, $foreign, $type);
                        }
                    }
                }
            }
        }
        return xSqlJoins::create($j);
    }

    /**
     * Determines whether the $key is an associative array index.
     * @param mixed Key to be tested.
     * @return bool True if $key represents an associative array index.
     */
    protected function is_assoc($key) {
        return !((int)$key === $key);
    }

    /**
     * Creates a new xSqlJoin.
     * @param string Local field desccription (table.field).
     * @param string Foreign field desccription (table.field).
     * @param string Type description (JOIN, LEFT JOIN, OUTER LEFT JOIN, ...).
     * @param string Operator description (=, !=, >, ...).
     * @return bool True if $key represents an associative array index.
     */
    protected function create_join($local, $foreign, $type=null, $operator=null) {
        // Extracts local field information
        $local_table = array_shift(array_slice(explode('.', $local), 0, 1));
        $local_field = array_shift(array_slice(explode('.', $local), 1, 1));
        $local_type = xSqlTypeDefault::create();
        // Extracts foreign field information
        $forgn_field = array_shift(array_slice(explode('.', $foreign), 0, 1));
        $forgn_table = array_shift(array_slice(explode('.', $foreign), 1, 1));
        $forgn_type = xSqlTypeDefault::create();
        // Extracts type information
        $type = $type ? explode(' ', $type) : null;
        // Create xSqlJoin instance
        return xSqlJoin::create(
            xSqlField::create($local_field, xSqlTable::create($local_table), $local_type),
            xSqlField::create($forgn_field, xSqlTable::create($forgn_table), $forgn_type),
            $type,
            $operator
        );
    }
}

/**
 * Creates a xSqlGroup clause from a structured description array.
 * @package xSql
 */
class xSqlRequestParserGroup extends xSqlRequestParser {

    /**
     * @return xSqlGroup Containing the parsed fields.
     */
    function parse() {
        $p = $this->params;
        $f = array();
        foreach ($p as $group) {
            list($table, $field) = explode('.', $group);
            $table = xSqlTable::create($table);
            $type = xSqlTypeDefault::create();
            $f[] = xSqlField::create($field, $table, $type);
        }
        return xSqlGroup::create($f);
    }
}

/**
 * Creates a xSqlParser clause from a structured description array.
 * @package xSql
 */
class xSqlRequestParserOrder extends xSqlRequestParser {

    /**
     * @return xSqlOrder Containing the parsed fields and directions.
     */
    function parse() {
        $p = $this->params;
        $f = array();
        foreach ($p as $key => $order) {
            list($x, $direction) = explode(' ', $order);
            list($table, $field) = explode('.', $x);
            $table = xSqlTable::create($table);
            $type = xSqlTypeDefault::create();
            $f[][$direction] = xSqlField::create($field, $table, $type);
        }
        return xSqlOrder::create($f);
    }

    /**
     * Determines whether the $key is an associative array index.
     * @param mixed Key to be tested.
     * @return bool True if $key represents an associative array index.
     */
    protected function is_assoc($key) {
        return !((int)$key === $key);
    }
}