<?php
/**
 * This file is part of MDE-Service
 * <http://github.com/piwi/mde-service>
 *
 * Copyright 2014 Pierre Cassat
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
