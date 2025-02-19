<?php
if(php_sapi_name()!=='cli') return;
require __DIR__.'/init.php';
$port=file_get_contents(__DIR__.'/port.txt');
$server='localhost:'.$port;
if(isset($argv[1])){
    if($argv[1]=='service-start'){
        $done=Manager::service_start();
    }
    elseif($argv[1]=='service-stop'){
        $done=Manager::service_stop();
    }
    elseif($argv[1]=='stop-all'){
        $done=Manager::service_stop();
        $done2=Manager::app_stop();
        $done3=Manager::php_stop();
    }
    elseif($argv[1]=='app-stop'){
        $done=Manager::app_stop();
    }
    elseif($argv[1]=='app-open'){
        $cli=EasyCLI::newCleanEnv('start http://'.$server);
        $cli->open();
    }
    elseif($argv[1]=='app-pid'){
        echo toJSON(Manager::lock_pid())."\n";
    }
    elseif($argv[1]=='nginx-test'){
        $done=Manager::nginx_test();
    }
    elseif($argv[1]=='php-stop'){
        $done=Manager::php_stop();
    }
    else{
        echo "Opcion invalida\n";
        exit(1);
    }
    exit();
}

if(!is_numeric($port)){
    exit(1);
}
# Bloqueo del proceso
$lockfile=Manager::lock_file();
$lock=fopen($lockfile, 'a');
if(!$lock) return false;
if(!flock($lock, LOCK_EX|LOCK_NB)){
    exit(1);
}
$service=EasyCLI::newCleanEnv([PHP_BINARY, '-S', $server, '-t', BASEDIR], ROOT_DIR)->open();
if(!$service){
    exit(1);
}
$pid=$service->pid().','.getmypid();
echo "Servicio iniciado ".$pid."\n";
$service->in_close();
$service->out_close();
$service->err_close();
fseek($lock, 0);
ftruncate($lock, 0);
fwrite($lock, $pid);
$inodo=fstat($lock)['ino'];
register_shutdown_function(function()use($service, $lock, $lockfile, $pid){
    $service->terminate();
    flock($lock, LOCK_UN);
    fclose($lock);
    ob_start();
    readfile($lockfile);
    $content=ob_get_clean();
    if($content===$pid){
        unlink($lockfile);
    }
});
$sleep=20;
while(1){
    sleep($sleep);
    if(!$service->is_running()) break;
    clearstatcache(true, $lockfile);
    $ino=@fileinode($lockfile);
    if(!$ino || $ino!=$inodo){
        $lock=null;
        if(($lock=fopen($lockfile, 'a')) && flock($lock, LOCK_EX|LOCK_NB) && ftruncate($lock, 0) && fwrite($lock, $pid)){
            $inodo=fstat($lock)['ino'];
            continue;
        }
        break;
    }
}
