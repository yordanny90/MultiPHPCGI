<?php
if(php_sapi_name()!=='cli') return;

error_reporting(E_COMPILE_ERROR | E_RECOVERABLE_ERROR | E_ERROR | E_CORE_ERROR);
ini_set('display_errors', 1);
ini_set('error_log', __DIR__.'\..\tmp\php_error.log');
define('BASEDIR', __DIR__);
chdir(BASEDIR);
define('ROOT_DIR', dirname(BASEDIR));
define('CONFIG_DIR', ROOT_DIR.'\conf');
define('SITES_DIR', CONFIG_DIR.'\nginx\conf\sites-enabled');
define('NGINX_LOG_DIR', CONFIG_DIR.'\nginx\logs');
define('INC_DIR', ROOT_DIR.'\inc');
define('INI_FILE', INC_DIR.'\config.ini');
define('APP_VER', '1.2');
require_once BASEDIR.'\.lib\__autoload.php';

$fn_list=[
    '-?'=>function(...$params) use (&$fn_list){
        echo "Opciones:\n";
        foreach($fn_list as $name=>$fn){
            echo "\t".$name."\n";
        }
    },
    'dir'=>function(...$params){
        echo ROOT_DIR;
    },
    'service-start'=>function(...$params){
        $done=Manager::service_start();
        if($done) echo 'Done';
        if(!$done) exit(1);
    },
    'service-stop'=>function(...$params){
        $done=Manager::service_stop();
        if(!$done) exit(1);
    },
    'nginx-test'=>function(...$params){
        $done=Manager::nginx_test();
        if(!$done) exit(1);
    },
    'nginx-log-clear'=>function(...$params){
        $done=Manager::nginx_log_clear();
        if(!$done) exit(1);
    },
    'php-stop'=>function(...$params){
        $done=Manager::php_stop();
        if(!$done) exit(1);
    },
    'php-list'=>function(...$params){
        $list=Manager::php_list();
        foreach($list as $l){
            echo $l."\n";
        }
    },
    'get-config'=>function(...$params){
        $config=Manager::getConfig();
        print_r($config);
    },
    'init-servers'=>function(...$params){
        $config=Manager::initServers();
        print_r($config);
    },
    'add-server'=>function(...$params){
        $config=Manager::addServer(...$params);
        print_r($config);
    },
    'process-list'=>function(...$params){
        Manager::process_list(...$params);
    },
];
if(isset($argv[1])){
    $params=array_slice($argv, 2);
    if(isset($fn_list[$argv[1]])){
        $fn_list[$argv[1]](...$params);
        exit();
    }
    else{
        echo "Opcion invalida\n";
        exit(1);
    }
}
$fn_list['-?']();