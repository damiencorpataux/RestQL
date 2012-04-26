<?php

// Requires PHPUnit library and dependancies
$vendors = '../vendors';
$phpunit = "{$vendors}/phpunit";
$fileiterator = "{$vendors}/php-file-iterator/";
$codecoverage = "{$vendors}/php-code-coverage/";
$tokenstream = "{$vendors}/php-token-stream/";
$texttemplate = "{$vendors}/php-text-template/";
$timer = "{$vendors}/php-timer/";
$mockobjects = "{$vendors}/phpunit-mock-objects";
set_include_path(get_include_path() . PATH_SEPARATOR . $phpunit);
set_include_path(get_include_path() . PATH_SEPARATOR . $fileiterator);
set_include_path(get_include_path() . PATH_SEPARATOR . $codecoverage);
set_include_path(get_include_path() . PATH_SEPARATOR . $tokenstream);
set_include_path(get_include_path() . PATH_SEPARATOR . $texttemplate);
set_include_path(get_include_path() . PATH_SEPARATOR . $timer);
set_include_path(get_include_path() . PATH_SEPARATOR . $mockobjects);
require "{$phpunit}/PHPUnit/Autoload.php";

/**
 * RestQL-specific PHPUnit Test Class.
 * @package Tests
 */
abstract class RestQL_PHPUnit_Framework_TestCase extends PHPUnit_Framework_TestCase
{
    function setUp() {
        // Loads RestQL Library
        require_once('../import.php');
    }

    /**
     * @return xSqlElement
     */
    protected function make_xsql() {
    }

    /**
     * @param array RestQL data structure.
     * @param string Expected SQL
     */
    protected function do_test() {
        $backtrace = debug_backtrace();
        $calling_function = $backtrace[1]['function'];
        $data_function = preg_replace('/^test_/', 'restql_', $calling_function);
        $expected_function = preg_replace('/^test_/', 'expected_', $calling_function);
        //
        $data = $this->$data_function();
        $expected = $this->$expected_function();
        $parser = $this->parser;
        $xsql = $parser::create($data)->parse();
        $this->assertSql($expected, $xsql);
    }

    protected function do_dump_expected($literalize=true) {
        $backtrace = debug_backtrace();
        $calling_function = $backtrace[1]['function'];
        $data_function = preg_replace('/^test_/', 'restql_', $calling_function);
        $expected_function = preg_replace('/^test_/', 'expected_', $calling_function);
        //
        $data = $this->$data_function();
        $xsql = xSqlRequestParserSelect::create($data)->parse();
        echo $literalize ? $this->literalize($xsql) : (string)$xsql;
    }

    /**
     * Asserts that the given $xsql object generates the expected SQL statement.
     * @param string The expected SQL string (literated).
     * @param array The RestQL data structure.
     * @return void
     * @throw PHPUnit_Framework_ComparisonFailure
     * @see literalize()
     */
    protected function assertSql($sql, xSqlElement $restql) {
        $expected = $this->deliteralize($sql);
        $actual = (string)$restql;
        return $this->assertSame($expected, $actual);
    }

    /**
     * Literalizes the given $string.
     * Returns $string with tabs, newline and return characters literalized.
     * @param string The string to literalize.
     * @return string
     */
    protected function literalize($string) {
        return str_replace(
            array("\t", "\n", "\r"),
            array('\t', '\n', '\r'),
            (string)$string
        );
    }

    /**
     * Deliteralizes the given $string.
     * Returns $string with tabs, newline and return characters deliteralized.
     * @param string The string to literalize.
     * @return string
     */
    protected function deliteralize($string) {
        return str_replace(
            array('\t', '\n', '\r'),
            array("\t", "\n", "\r"),
            (string)$string
        );
    }
}

// PHPUnit autorun
PHPUnit_TextUI_Command::main();