<?php
if(php_sapi_name()!=='cli') return;

error_reporting(E_COMPILE_ERROR | E_RECOVERABLE_ERROR | E_ERROR | E_CORE_ERROR);
require_once __DIR__.'\.lib\__autoload.php';
ini_set('display_errors', 0);
define('ORIGINAL_DIR', getcwd());
define('ROOT_DIR', dirname(__DIR__, 2));
ini_set('error_log', ROOT_DIR.'\tmp\php_error.log');
define('APP_DIR_BIN', ROOT_DIR.'\bin');
define('APP_DIR_PHP', ROOT_DIR.'\php');
define('APP_DIR_NGINX', ROOT_DIR.'\nginx');
define('APP_DIR_CONFIG', ROOT_DIR.'\conf');
define('SITES_DIR', APP_DIR_CONFIG.'\nginx\conf\sites-enabled');
define('NGINX_LOG_DIR', APP_DIR_CONFIG.'\nginx\logs');
define('APP_DIR_INC', ROOT_DIR.'\inc');
define('INI_FILE', APP_DIR_INC.'\config.ini');
define('APP_VER', '1.3');

$fn_list=[
    '-?'=>function() use (&$fn_list){
        echo "Opciones:\n";
        foreach($fn_list as $name=>$fn){
            echo "\t".$name."\n";
        }
    },
    '-v'=>function(){
        echo APP_VER;
    },
    'dir'=>function(){
        echo ROOT_DIR;
    },
    'service-start'=>function(){
        $pids=Manager::service_start();
        foreach($pids as $name=>$pid){
            echo $name.", PID:".$pid."\n";
        }
    },
    'service-stop'=>function(){
        $done=Manager::service_stop();
        echo 'Procesos detenidos: '.$done;
    },
    'nginx-test'=>function(){
        $done=Manager::nginx_test();
        if(!$done) exit(1);
    },
    'nginx-log-clear'=>function(){
        Manager::nginx_log_clear();
    },
    'php-stop'=>function(){
        $done=Manager::php_stop();
        if(!$done) exit(1);
    },
    'php-ver'=>function($ver=null){
        echo Manager::php_find_version($ver);
    },
    'php-bin'=>function($ver=null){
        if(substr_count($ver, '.')<2) $ver=Manager::php_find_version($ver);
        echo Manager::php_bin($ver);
    },
    'phpcgi-bin'=>function($ver=null){
        if(substr_count($ver, '.')<2) $ver=Manager::php_find_version($ver);
        echo Manager::phpcgi_bin($ver);
    },
    'php-ini'=>function($ver=null){
        if(substr_count($ver, '.')<2) $ver=Manager::php_find_version($ver);
        echo Manager::php_ini($ver);
    },
    'php-list'=>function(){
        echo implode("\n", Manager::php_list());
    },
    'php-list-online'=>function($dl=null){
        echo implode("\n", Manager::php_nts_list_online(boolval($dl)));
    },
    'php-bat'=>function(){
        $list=Manager::php_make_bat();
        print_r($list);
    },
    'test-ini'=>function($ver=null){
        $file=Manager::php_ini($ver);
        if(!$file){
            throw new ResponseErr('Archivo no encontrado');
        }
        $changes=[
            'PHP'=>[
                'extension_dir',
                'extension',
                'zend_extension',
            ],
            'opcache'=>[
                'opcache.enable',
            ],
        ];
        echo $file."\n";
        echo "\n--- Habilitados ---\n\n";
        Manager::update_ini($file, [], $changes, [], null, true);
        echo "\n--- Deshabilitados ---\n\n";
        Manager::update_ini($file, [], [], $changes, null, true);
    },
    'port-listening'=>function(?string $port=null){
        $ports=Manager::portListening($port);
        foreach($ports as $port){
            print_r($port);
        }
    },
    'get-url'=>function($https=null, ?string $port=null){
        $suffix=(is_numeric($port) && $port>1?':'.intval($port):'');
        $https=($https?'https':'http');
        $ip_list=Manager::getIPList();
        foreach($ip_list['IPv4'] as $ip){
            echo $https.'://'.$ip.$suffix."/\n";
        }
        foreach($ip_list['IPv6'] as $ip){
            echo $https.'://['.$ip."]".$suffix."/\n";
        }
    },
    'get-ip'=>function(){
        $ip_list=Manager::getIPList();
        foreach($ip_list['IPv4'] as $ip){
            echo $ip."\n";
        }
        foreach($ip_list['IPv6'] as $ip){
            echo $ip."\n";
        }
    },
    'get-ip-cert'=>function($offset=null){
        $i=intval($offset);
        $ip_list=Manager::getIPList();
        foreach($ip_list['IPv4'] as $ip){
            echo "IP.".(++$i)." = ".$ip."\n";
        }
        foreach($ip_list['IPv6'] as $ip){
            echo "IP.".(++$i)." = ".$ip."\n";
        }
    },
    'get-server'=>function(?string $name=null){
        $servers=Manager::getServer_list();
        if($name!==null){
            if(!isset($servers[$name])) throw new ResponseErr('Server no encontrado');
            echo "[$name]\n";
            array_walk($servers[$name], function($v,$k){echo " ".$k."=".$v."\n";});
            return;
        }
        foreach($servers as $n=>$server){
            echo "[$n]\n";
            array_walk($server, function($v,$k){echo " ".$k."=".$v."\n";});
        }
    },
    'init-servers'=>function(...$_){
        Manager::initServers(...$_);
    },
    'add-server'=>function(...$_){
        Manager::addServer(...$_);
    },
    'get-process'=>function(...$_){
        Manager::process_list(...$_);
    },
];
try{
    if(!isset($argv[1])){
        $fn_list['-?']();
        return;
    }
    $params=array_slice($argv, 2);
    if(isset($fn_list[$argv[1]])){
        $fn_list[$argv[1]](...$params);
    }
    else{
        throw new ResponseErr('Opcion inválida');
    }
}
catch(ResponseErr $e){
    file_put_contents('php://stderr', $e->getMessage());
    exit(1);
}
catch(Throwable $e){
    file_put_contents('php://stderr', $e);
    exit(1);
}