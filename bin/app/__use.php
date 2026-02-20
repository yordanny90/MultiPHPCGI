<?php
require_once __DIR__.'/.lib/__autoload.php';
define('MPHPCGI_CWD', getcwd());
define('MPHPCGI_ROOT_DIR', dirname(__DIR__, 2));
define('MPHPCGI_DIR_BIN', MPHPCGI_ROOT_DIR.'\bin');
define('MPHPCGI_DIR_PHP', MPHPCGI_ROOT_DIR.'\php');
define('MPHPCGI_DIR_NGINX', MPHPCGI_ROOT_DIR.'\nginx');
define('MPHPCGI_DIR_USR', MPHPCGI_ROOT_DIR.'\usr');
define('MPHPCGI_DIR_SITES', MPHPCGI_DIR_USR.'\servers');
define('MPHPCGI_DIR_INC', MPHPCGI_ROOT_DIR.'\inc');
define('MPHPCGI_VER', '1.3.2');
