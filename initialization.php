<?php
// App constants
define('APP_NAME',          'Hourglass');
define('APP_ROOT',          dirname(__FILE__));
define('APP_TMP',           APP_ROOT.'/tmp/');
define('SRC_MAIN',          APP_ROOT.'/'.APP_NAME.'/Managers/');
define('ENDPOINT_DIR',      APP_ROOT.'/endpoint/');
define('FIRST_REV_NUMBER',  1);

// include config and composer autoload
require_once 'config.php';
require_once 'vendor/autoload.php';

// Configure Log4php Logger
Logger::configure(APP_ROOT.'/hourglass_logging.properties');

// Create array for cached request
$REQUESTS_TO_BE_CACHED = Array();


