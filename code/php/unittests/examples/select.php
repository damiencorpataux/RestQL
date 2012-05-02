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

    /**
     * SELECT with tables and fields.
     */
    function test_table() {
        $this->do_test();
    }

    /**
     * SELECT with fields and aliases.
     */
    function test_alias() {
        $this->do_test();
    }

    /**
     * SELECT with tables, fields and aliases.
     */
    function test_table_alias() {
        $this->do_test();
    }

    /**
     * SELECT with all possible components and flavours.
     */
    function test_complete() {
        $this->do_test();
    }
}