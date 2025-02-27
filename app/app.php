<?php
if(php_sapi_name()!=='cli') return;
require __DIR__.'/init.php';
if(isset($argv[1])){
    if($argv[1]=='service-start'){
        $done=Manager::service_start();
        if($done) echo 'Done';
        if(!$done) exit(1);
    }
    elseif($argv[1]=='service-stop'){
        $done=Manager::service_stop();
        if(!$done) exit(1);
    }
    elseif($argv[1]=='stop-all'){
        $done=Manager::service_stop();
        $done2=Manager::app_stop();
        $done3=Manager::php_stop();
        if(!$done || !$done2 || !$done3) exit(1);
    }
    elseif($argv[1]=='app-start'){
        $done=Manager::app_start();
        if(!$done) exit(1);
    }
    elseif($argv[1]=='app-stop'){
        $done=Manager::app_stop();
        if(!$done) exit(1);
    }
    elseif($argv[1]=='app-open'){
        $done=Manager::app_open();
        if(!$done) exit(1);
    }
    elseif($argv[1]=='app-pid'){
        echo toJSON(Manager::lock_pid())."\n";
    }
    elseif($argv[1]=='nginx-test'){
        $done=Manager::nginx_test();
        if(!$done) exit(1);
    }
    elseif($argv[1]=='php-stop'){
        $done=Manager::php_stop();
        if(!$done) exit(1);
    }
    else{
        echo "Opcion invalida\n";
        exit(1);
    }
}
exit();
