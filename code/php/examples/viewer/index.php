<?php
/*
 * (c) 2012 Damien Corpataux
 *
 * Licensed under the GNU GPL v3.0 license,
 * accessible at http://www.gnu.org/licenses/gpl-3.0.html
 *
**/
set_error_handler(function() {
    null;
});

// Constants definition
define('DIRECTORY_EXAMPLES', '../../unittests/specifications');
define('DIRECTORY_SPECIFICATIONS', '../../../../specifications/data');

define('CASES', 'select,from,joins,where,group,order,offset,fuzzy');
define('CASE_DEFAULT', array_shift(explode(',', CASES)));

// Request management
@list($case, $test) = explode(':', @$_REQUEST['run']);
define('CASE', $case ? strtolower($case) : CASE_DEFAULT);
define('TEST', $test);

// Page display
require 'View.php';
$side = xView::create('tpl/list.tpl', list_examples())->render();
$body = xView::create('tpl/detail.tpl', array(
    'json' => get_json(),
    'sql' => parse(),
    'request' => array(
        'case' => $case
    )
))->render();
$page = xView::create('tpl/layout.tpl', array(
    'html' => array(
        'side' => $side,
        'body' => $body
    ),
    'request' => array(
        'case' => $case,
        'test' => $test
    ),
    'data' => array(
        'cases' => explode(',', CASES),
        'case_default' => array_shift(explode(',', CASES))
    )
))->render();
echo $page;


/******************************************************************************
 * API
 */

// Returns an array of tested examples
function list_tests() {
    $directory = DIRECTORY_EXAMPLES;
    // Retireves test files
    $files = array_filter(scandir($directory), function($file) {
        return !in_array($file, array('.', '..'));
    });
    $tests = array();
    foreach ($files as $file) {
        $uri = "$directory/$file";
        $stream = file_get_contents($uri);
        preg_match_all('/function test_(.*)\s*\(.*\)/', $stream, $matches);
        $case = str_replace('.php', null, $file);
        if (@$matches[1]) $tests[$case] = $matches[1];
    }
    return $tests;
}

function list_examples() {
    $directory = DIRECTORY_SPECIFICATIONS;
    // Retieves cases
    $cases = array_filter(scandir($directory), function($file) {
        return !in_array($file, array('.', '..'));
    });
    // Orders cases
    usort($cases, function($a, $b) {
        $cases = array_map('trim', explode(',', CASES));
        return array_search(strtolower($a), $cases) > array_search(strtolower($b), $cases);
    });
    // Retrieves examples for cases
    $examples = array();
    foreach ($cases as $case) {
        // Fetches examples files
        $files = array_filter(scandir("$directory/$case"), function($file) {
            return !in_array($file, array('.', '..'));
        });
        // Trims files extension and makes examples unique
        $files = array_unique(array_map(function($item) {
            return str_replace(array('.json', '.sql'), null, $item);
        }, $files));
        $examples[$case] = $files;
    }
    return $examples;
}

// Returns the current JSON, or the selected example JSON
function get_json() {
    $example = @$_REQUEST['run'];
    $json = @$_REQUEST['json'];
    if ($json) {
        return $json;
    } elseif (TEST) {
        // Retrieves examples
        $case = constant('CASE');
        $test = constant('TEST');
        $json = file_get_contents(DIRECTORY_SPECIFICATIONS."/{$case}/{$test}.json");
        return $json;
    } else {
        return '["welcome"]';
    }
}

// Returns SQL generated from parsed JSON
function parse() {
    // Librairies
    require_once('../../unittests/phpunit.php'); // PHPUnit
    require_once('../../import.php');            // RestQL
    //
    $json = get_json();
    $data = json_decode($json, true);
    if ($data === null) return null;
    $case = constant('CASE');
    $parser = "xSqlRequestParser{$case}";
    try {
        return $parser::create($data)->parse();
    } catch (Exception $e) {
        return $e->getMessage();
    }
}