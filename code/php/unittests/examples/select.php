<?php
/*
 * (c) 2012 Damien Corpataux
 *
 * Licensed under the GNU GPL v3.0 license,
 * accessible at http://www.gnu.org/licenses/gpl-3.0.html
 *
**/

class ExampleSelectTests extends RestQL_PHPUnit_Framework_TestCase {

    protected $parser = 'xSqlRequestParserSelect';

    /**
     * SELECT with fields.
     */
    function test_basic() {
        $this->do_test();
    }
    function restql_basic() {
        return array('field-1', 'field-2');
    }
    function expected_basic() {
        return 'SELECT\t`field-1`,\n\t`field-2`';
    }

    /**
     * SELECT with tables and fields.
     */
    function test_table() {
        $this->do_test();
    }
    function restql_table() {
        return array(
            'field0',
            'table-1' => 'field-1',
            'table-2' => array('field-2-1', 'field-2-2')
        );
    }
    function expected_table() {
        return 'SELECT\t`field0`,\n\t`table-1`.`field-1`,\n\t`table-2`.`field-2-1`,\n\t`table-2`.`field-2-2`';
    }

    /**
     * SELECT with fields and aliases.
     */
    function test_alias() {
        $this->do_test();
    }
    function restql_alias() {
        return array(
            array('field-1' => 'alias-1'),
            array('field-2' => 'alias-2')
        );
    }
    function expected_alias() {
        return 'SELECT\t`alias-1` AS `field-1`,\n\t`alias-2` AS `field-2`';
    }

    /**
     * SELECT with tables, fields and aliases.
     */
    function test_table_alias() {
        $this->do_test();
    }
    function restql_table_alias() {
        return array(
            'table-1' => array('alias-1' => 'field-1'),
            'table-2' => array(
                'alias-2-1' => 'field-2-1',
                'alias-2-2' => 'field-2-2'
            )
        );
    }
    function expected_table_alias() {
        return 'SELECT\t`table-1`.`field-1` AS `alias-1`,\n\t`table-2`.`field-2-1` AS `alias-2-1`,\n\t`table-2`.`field-2-2` AS `alias-2-2`';
    }


    /**
     * SELECT with all possible components and flavours.
     */
    function test_complete() {
        $this->do_test();
    }
    function restql_complete() {
        return array(
            'field-0',
            'table-1' => 'field-1',
            'table-1' => array('field-1-1', 'field-1-2'),
            'table-3' => array('alias-3' => 'field-3'),
            /*
            'table-4' => array(
                array('alias-4-1' => 'field-4-1'),
                array('alias-4-2' => 'field-4-2')
            )
            */
        );
    }
    function expected_complete() {
        return 'SELECT\t`field-0`,\n\t`table-1`.`field-1-1`,\n\t`table-1`.`field-1-2`,\n\t`table-3`.`field-3` AS `alias-3`';
    }
}