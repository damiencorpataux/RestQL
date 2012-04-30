<style>
textarea {
    width:30%;
    height:250px;
    float:left;
    margin-right:10px
}
.message {
    border: 1px solid #f50;
    background-color: #fda;
    padding: 5px;
    margin: 5px;
    width: 860px;
}
.error {
    background-color: #faa;
}
</style>

<?php
/*
 * (c) 2012 Damien Corpataux
 *
 * Licensed under the GNU GPL v3.0 license,
 * accessible at http://www.gnu.org/licenses/gpl-3.0.html
 *
**/

$dir_examples = '../../unittests/examples';
$message = null;

// Lists examples
$tests = list_tests($dir_examples);
echo '<ul>';
foreach ($tests as $file => $tests) {
    $suite = preg_replace('/.*\/(\w*)\.php$/', '$1', $file);
    echo "<li>$suite</li>";
    echo '<ul>';
    foreach ($tests as $test) {
        printf('<li> <a href="?example=%s:%s">%s</a>', $suite, $test, $test);
    }
    echo '</ul>';
}
echo '</ul>';
echo '<hr/>';


// Shows example details
if ($example = @$_REQUEST['example']) {
    // Libraries
    require_once('../../unittests/phpunit.php');
    require_once('../../import.php');
    //
    list($suite, $test) = explode(':', $example);
    //
    if (@$_REQUEST['data']) {
        $data = json_decode($_REQUEST['data'], true);
        if ($data === null) {
            $message = "Invalid json";
            $data = $_REQUEST['data'];
        }
    } else {
        // Retrieves test data
        $suitefile = "$dir_examples/$suite.php";
        require_once($suitefile);
        $suite_class = "Example{$suite}Tests";
        $suite_instance = new $suite_class();
        $data_method = "restql_$test";
        $data = $suite_instance->$data_method();
    }
    // Computes result
    $parser_class = "xSqlRequestParser{$suite}";
    $result = $parser_class::create(is_array($data) ? $data : array())->parse();
    // Display HTML
    $json = is_array($data) ? json_encode($data) : $data;
    $class = $message ? 'error' : null;
    if ($message) printf('  <div class="message">%s</div>', $message);
    echo "<h1>$example</h1>";
    printf('<form>');
    printf('  <textarea name="data" class="%s" style="width:30%%;height:300px">%s</textarea>', $class, $json);
    printf('  <textarea readonly style="width:30%%;height:300px">%s</textarea>', $result);
    printf('  <div style="clear:both"/>');
    printf('  <input type="hidden" name="example" value="%s"/>', $example);
    printf('  <input type="submit" value="Parse RestQL"/>');
    printf('</form>');
}

// API
function list_tests($directory) {
    // Retireves test files
    $files = array_filter(scandir($directory), function($file) {
        return !in_array($file, array('.', '..'));
    });
    $tests = array();
    foreach ($files as $file) {
        $uri = "$directory/$file";
        $stream = file_get_contents($uri);
        preg_match_all('/function test_(.*)\s*\(.*\)/', $stream, $matches);
        if (@$matches[1]) $tests[$uri] = $matches[1];
    }
    return $tests;
}