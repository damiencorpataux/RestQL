<?php
/*
 * (c) 2012 Damien Corpataux
 *
 * Licensed under the GNU GPL v3.0 license,
 * accessible at http://www.gnu.org/licenses/gpl-3.0.html
 *
**/

// Requires PHPUnit library and dependancies
$vendors = __DIR__.'/../vendors';
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

    public $path_json = '../../../specifications/data/';

    function setUp() {
        // Loads RestQL Library
        require_once('../import.php');
    }

    /**
     * @param array RestQL data structure.
     * @param string Expected SQL
     */
    protected function do_test() {
        // Computes testcase and test names
        $backtrace = debug_backtrace();
        $calling_function = $backtrace[1]['function'];
        $calling_class = $backtrace[1]['class'];
        preg_match('/Example(.+)Tests/', $calling_class, $matches);
        $case = strtolower(@$matches[1]);
        $test = strtolower(str_replace('test_', null, $calling_function));
        if (!$case || !$test) throw new Exception('Could not compute testcase name and/or test name');
        // Retrieves JSON source
        $json = file_get_contents("{$this->path_json}/{$case}/{$test}.json");
        $sql = file_get_contents("{$this->path_json}/{$case}/{$test}.sql");
        // Setups test components
        $parser = $this->parser;
        $data = json_decode($json, true);
        $expected = $sql;
        if (!$data) throw new Exception("Invalid JSON: {$json}");
        // Actual test
        $xsql = $parser::create($data)->parse();
        $this->assertSql($expected, $xsql);
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
        $expected = $sql;
        $actual = (string)$restql;
        return $this->assertSame($expected, $actual);
    }
}

// PHPUnit autorun
if (PHP_SAPI==='cli') PHPUnit_TextUI_Command::main();