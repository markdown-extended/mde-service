<?php
/**
 * see <http://github.com/piwi/mde-service>
 */

// path to the PHP namespaces autoloader (here from Composer)
// see <http://getcomposer.org/doc/00-intro.md#using-composer>
$autoloader = __DIR__.'/../vendor/autoload.php';

// define some specific settings for the webservice
@ini_set('display_errors',1);
@ini_set('html_errors', 0);
@error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
if (function_exists('xdebug_disable')) {
    xdebug_disable();
}

// get the composer autoloader
if (file_exists($autoloader)) {
    require_once $autoloader;
} else {
    exit('No autoloader found for application namespaces!');
}

/*/
// use this to debug request data
if (!empty($_GET)) { echo "# GET data:".PHP_EOL; var_dump($_GET); }
if (!empty($_POST)) { echo "# POST data:".PHP_EOL; var_dump($_POST); }
if (!empty($_FILES)) { echo "# FILES data:".PHP_EOL; var_dump($_FILES); }
//*/

// distribute the request, parse received content and serve the JSON response
\MdeService\Controller::create()
    ->distribute()
    ->parse()
    ->serve();

// big oops!!
exit('Something has gone terribly wrong :(');
