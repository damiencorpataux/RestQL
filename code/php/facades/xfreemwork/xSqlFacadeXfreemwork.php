<?php
/*
 * (c) 2012 Damien Corpataux
 *
 * LICENSE
 * This library is licensed under the GNU GPL v3.0 license,
 * accessible at http://www.gnu.org/licenses/gpl-3.0.html
 *
**/

/**
 * Manages xSqlElements.
 * Useful for keeping singletons of xSqlElement (eg. xSqlField or xSqlTable).
 * @package xSql
 */
abstract class xSqlManager {

    public $items = array();

    /**
     * Adds an item.
     * @param xSqlElement
     */
    abstract public function put($item);

    /**
     * Returns item matching $id, null otherwise.
     * @return xSqlElement|null
     */
    abstract public function get($id);

    /**
     * Returns items matching $criterion
     * @return array
     */
    abstract public function find($criterion);

    /**
     * Returns items index matching $criterion
     * @return array
     */
    abstract public function search($criterion);
}
//class xSqlManagerTable extends xSqlManager {}
//class xSqlManagerField extends xSqlManager {}


/**
 * Base connector for building xSqlField from various framework.
 * @package xSql
 */
abstract class xSqlModel {

    /**
     * xSqlField representation for model fields.
     * @var array
     */
    public $fields = array();

    /**
     * @param string Source specific model identifier.
     * @return array Array xSqlField
     */
    abstract function create($source);

}

/**
 * xFreemwork connector for building xSqlField from models.
 * @package xSql
 */
class xSqlModelXfreemwork extends xSqlModel {

    /**
     * TODO: Manage xModel join (which adds foreign fields to mapping)
     * @param string Source xModel name.
     * @return array
     */
    function create($source) {
        $fields = array();
        $model = xModel::load($source);
        $mapping = array_merge($model->mapping, $model->foreign_mapping());
        foreach ($mapping as $modelfield => $dbfield) {
            $table = null; //TODO
            $type = null; //TODO
            $fields[] = xSqlFactory::create('Field',
                $dbfield,
                $table,
                $type,
                $modelfield
            );
        }
        return $fields;
    }
}