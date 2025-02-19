<?php
error_reporting(E_COMPILE_ERROR|E_RECOVERABLE_ERROR|E_ERROR|E_CORE_ERROR);
ini_set('error_log', __DIR__.'\..\tmp\php_error.log');
define('BASEDIR', __DIR__);
chdir(BASEDIR);
define('ROOT_DIR', dirname(BASEDIR));
define('CONFIG_DIR', ROOT_DIR.'\conf');
define('INC_DIR', ROOT_DIR.'\inc');
define('INI_FILE', CONFIG_DIR.'\app.ini');
define('APP_VER', '1.2');

require_once BASEDIR.'\.lib\__autoload.php';
include BASEDIR."\.lib\Charset_helper.php";
