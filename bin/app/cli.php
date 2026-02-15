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
define('APP_DIR_USR', ROOT_DIR.'\usr');
define('APP_DIR_SITES', APP_DIR_USR.'\servers');
define('APP_DIR_INC', ROOT_DIR.'\inc');
define('APP_VER', '1.3.2');
define('_DEF', "\033[0m");
define('_RED', "\033[31m");
define('_GREEN', "\033[32m");
define('_YELL', "\033[33m");
define('_BLUE', "\033[34m");
define('_PURP', "\033[35m");
define('_AQUA', "\033[36m");
define('_GREY', "\033[37m");
define('_BGRED', "\033[41m");
define('_BGYEL', "\033[43m");

$cli=new class{
    public $interative=false;
    public $list_opt=[];
    public $alias=[
        '/?'=>'help',
    ];

    /**
     * @param array $keys Dos tipos: `/bool` `-n "value"`
     * @param array $params
     * @return array
     */
    protected function _sets(array $keys, array $params){
        $data=array_fill_keys($keys, null);
        $o=null;
        foreach($params AS $p){
            if($o===null && in_array($p, $keys, true)){
                if(substr($p, 0, 1)=='/'){
                    $data[$p]=true;
                }
                else{
                    $o=$p;
                }
                continue;
            }
            if($o!==null){
                $data[$o]=$p;
                $o=null;
                continue;
            }
        }
        if($o!==null){
            $data[$o]=$p;
        }
        return $data;
    }

    /**
     * @param bool $csv Imprime el formato csv o tabulado
     * @param ...$data
     * @return void
     */
    protected static function _print($csv, ...$data){
        if($csv) echo implode(',', array_map(function($v){
            if($v==='' || $v===null) return '';
            if(is_numeric($v)) return $v;
            return '"'.str_replace('"', '""', $v).'"';
        }, $data))."\n";
        else echo implode(" \t", $data)."\n";
    }

    public function __construct(){
        $this->list_opt=array_keys($this->alias);
        foreach((new ReflectionObject($this))->getMethods(ReflectionMethod::IS_PUBLIC) as $fn){
            $name=$fn->getName();
            if(substr($name, 0, 1)==='_') continue;
            $this->list_opt[]=$name;
        }
    }

    public function _opt($name, ...$params){
        $name=$this->alias[$name] ?? $name;
        if(substr($name, 0, 1)!=='_' && is_callable([
                $this,
                $name
            ])){
            $this->$name(...$params);
            return;
        }
        throw new ResponseErr('Opcion inválida');
    }

    /**
     * @note Muestra la ayuda
     * @return void
     */
    function help(){
        echo "Opciones:\n";
        if(!$this->interative) echo "  "._GREEN."-i-"._GREY."\n    Inicia el modo interactivo\n"._DEF;
        foreach((new ReflectionObject($this))->getMethods(ReflectionMethod::IS_PUBLIC) as $fn){
            $name=$fn->getName();
            if(substr($name, 0, 1)==='_') continue;
            $alias=array_keys(array_intersect($this->alias, [$name]));
            if($alias) echo _GREEN."  ".implode("\n  ", $alias)."\n";
            echo _GREEN."  ".$name." "._DEF;
            if(preg_match('/@xparams\s(.*)/', $fn->getDocComment(), $m)){
                echo $m[1]."\n";
            }
            else{
                echo implode(' ', array_map(function(ReflectionParameter $v){ return ($v->isVariadic()?$v->getName().' ...':('"'.$v->getName().'"')); }, $fn->getParameters()))."\n";
            }
            echo _GREY;
            if(preg_match('/@note\s(.*)/', $fn->getDocComment(), $m)){
                echo "    ".$m[1]."\n";
            }
        }
        if($this->interative) echo "  "._GREEN."x\n  exit"._GREY."\n    Salir del modo interactivo\n"._DEF;
        echo _DEF;
    }

    /**
     * @note Información de la versión
     * @return void
     */
    function ver(){
        echo "MultiPHPCGI v".APP_VER."\n";
        echo ROOT_DIR."\n";
    }

    /**
     * @note Reinicia los servicios web
     * @return void
     */
    function start(){
        $pids=Manager::start();
        foreach($pids as $name=>$pid){
            echo $name.", PID:".$pid."\n";
        }
    }

    /**
     * @note Detiene los servicios web
     * @return void
     */
    function stop(){
        $done=Manager::stop();
        echo 'Procesos detenidos: '.$done;
    }

    /**
     * @note Prueba configuración de NGINX
     * @return void
     */
    function nginx_test(){
        Manager::nginx_test();
    }

    /**
     * @note Limpia los logs de NGINX
     * @return void
     */
    function nginx_log_clear(){
        $fail='';
        $list=glob(APP_DIR_USR.'\\conf-nginx-*\\logs\\*.log');
        foreach($list as $file){
            if(!unlink($file)) $fail.="Fail: $file\n";
            else echo "Clear: ".$file."\n";
        }
        if($fail){
            throw new ResponseErr($fail);
        }
    }

    /**
     * @note Instala una versión de NGINX
     * @return void
     */
    function install_nginx(string $ver=''){
        Manager::install_nginx($ver);
    }

    /**
     * @note Detiene los procesos de php.exe y php-win.exe
     * @return void
     */
    function killphp(){
        $done=Manager::php_stop();
        echo 'Procesos detenidos: '.$done;
    }

    /**
     * @note Obtiene la versión completa mas reciente de PHP a apartir de una versión simple. Ejemplo 8.4 => 8.4.15
     * @return void
     */
    function php_ver($ver=null){
        echo Manager::php_find_version($ver);
    }

    /**
     * @note Obtiene la ruta del php.exe de la versión
     * @return void
     */
    function php_bin($ver=null){
        if(substr_count($ver, '.')<2) $ver=Manager::php_find_version($ver);
        $res=Manager::php_bin($ver);
        if($res===null) throw new ResponseErr('No encontrado');
        echo $res;
    }

    /**
     * @note Obtiene la ruta del php-cgi.exe de la versión
     * @return void
     */
    function phpcgi_bin($ver=null){
        if(substr_count($ver, '.')<2) $ver=Manager::php_find_version($ver);
        $res=Manager::phpcgi_bin($ver);
        if($res===null) throw new ResponseErr('No encontrado');
        echo $res;
    }

    /**
     * @note Obtiene la ruta del php.ini de la versión
     * @return void
     */
    function php_ini($ver=null){
        if(substr_count($ver, '.')<2) $ver=Manager::php_find_version($ver);
        $res=Manager::php_ini($ver);
        if($res===null) throw new ResponseErr('No encontrado');
        echo $res;
    }

    /**
     * @note Obtiene la lista de versiones de php instaladas
     * @return void
     */
    function php_list(){
        $list=Manager::php_list();
        echo implode("\n", $list);
    }

    /**
     * @xparams [/f]
     * @note Obtiene la lista de versiones de php online que se pueden instalar
     * @return void
     */
    function php_list_online(...$params){
        $sets=self::_sets(['/f'], $params);
        $list=Manager::php_nts_list_online(boolval($sets['/f']));
        echo implode("\n", $list);
    }

    /**
     * @note Regenera todos los .bat de php (php*.bat y php-cgi*.bat)
     * @return void
     */
    function php_bat(){
        $list=Manager::php_make_bat();
        echo implode("\n", $list);
    }

    /**
     * @xparams [-port #] [/https] [/ipv4] [/ipv6]
     * @note Genera todas las urls con las IPv4/IPv6 de este equipo para el protocolo http/https y con el puerto indicado
     * @return void
     */
    function get_url(...$params){
        $sets=self::_sets(['-port', '/https','/ipv4','/ipv6'], $params);
        $suffix=(is_numeric($sets['-port']) && $sets['-port']>1?':'.intval($sets['-port']):'');
        $https=(($sets['/https'] ?? null)?'https':'http');
        $ip_list=Manager::getIPList();
        if(!$sets['/ipv4'] && !$sets['/ipv6']) $sets['/ipv4']=$sets['/ipv6']=true;
        if($sets['/ipv4']) foreach($ip_list['IPv4'] as $ip){
            echo $https.'://'.$ip.$suffix."/\n";
        }
        if($sets['/ipv6']) foreach($ip_list['IPv6'] as $ip){
            echo $https.'://['.$ip."]".$suffix."/\n";
        }
    }

    /**
     * @xparams [/ipv4] [/ipv6]
     * @note Obtiene la lista de IPv4/IPv6 de este equipo
     * @return void
     */
    function get_ip(...$params){
        $sets=self::_sets(['/ipv4','/ipv6'], $params);
        $ip_list=Manager::getIPList();
        if(!$sets['/ipv4'] && !$sets['/ipv6']) $sets['/ipv4']=$sets['/ipv6']=true;
        if($sets['/ipv4']) foreach($ip_list['IPv4'] as $ip){
            echo $ip."\n";
        }
        if($sets['/ipv6']) foreach($ip_list['IPv6'] as $ip){
            echo $ip."\n";
        }
    }

    /**
     * @xparams [-off #] [/ipv4] [/ipv6]
     * @note Genera la lista de IP para agregarlas a la configuración del certificado
     * @return void
     */
    function get_ip_cert(...$params){
        $sets=self::_sets(['-off', '/ipv4','/ipv6'], $params);
        $i=intval($sets['-off']);
        $ip_list=Manager::getIPList();
        if(!$sets['/ipv4'] && !$sets['/ipv6']) $sets['/ipv4']=$sets['/ipv6']=true;
        if($sets['/ipv4']) foreach($ip_list['IPv4'] as $ip){
            echo "IP.".(++$i)." = ".$ip."\n";
        }
        if($sets['/ipv6']) foreach($ip_list['IPv6'] as $ip){
            echo "IP.".(++$i)." = ".$ip."\n";
        }
    }

    /**
     * @xparams ["server.*"]
     * @note Obtiene las configuraciones de los servidores configurados
     * @return void
     */
    function get_server($name=null){
        $servers=Manager::getServer_list();
        if($name!==null){
            if(!isset($servers[$name])) throw new ResponseErr('Server no encontrado');
            echo "[$name]\n";
            array_walk($servers[$name], function($v, $k){ echo " ".$k."=".$v."\n"; });
            return;
        }
        foreach($servers as $n=>$server){
            echo "[$n]\n";
            array_walk($server, function($v, $k){ echo " ".$k."=".$v."\n"; });
        }
    }

    /**
     * @xparams ["server.*"]
     * @note Genera los archivos .conf (NGINX) inexistentes para los servidores configurados
     * @return void
     */
    function make_servers($server=null){
        $list=Manager::initServers($server);
        echo implode("\n", $list);
    }

    /**
     * @xparams [-d "path"] [-v "phpver"]
     * @note Configura un nuevo servidor de forma interactiva
     * @return void
     */
    function new_server(...$params){
        $sets=self::_sets(['-d', '-v'], $params);
        Manager::addServer($sets['-d'], $sets['-v']);
    }

    /**
     * @xparams [-ports "#,#,#,..."] [-range #-#] [/csv]
     * @note Detecta si los puertos se estan escuchando y los procesos que los usan
     * @return void
     */
    function listen(...$params){
        $maxP=50;
        $sets=self::_sets(['/csv','-ports','-range'], $params);
        $ports=[];
        if($sets['-ports']) $ports=array_filter(array_map('intval', explode(',', $sets['-ports'])));
        if($sets['-range']){
            list($min, $max)=explode('-', $sets['-range'], 2);
            if(is_numeric($min) && $min>0 && is_numeric($max) && $max>0){
                $min=intval($min);
                $max=intval($max);
                $count=abs($max-$min)+1;
                if($count>$maxP) throw new ResponseErr('Máximo puertos: '.$maxP);
                $ports=[...$ports, ...array_keys(array_fill(min($min, $max), $count, 0))];
            }
        }
        $proc=[];
        $ports=array_unique($ports);
        $count=count($ports);
        if($count>$maxP) throw new ResponseErr('Máximo puertos: '.$maxP);
        foreach($ports as $port){
            if(!is_numeric($port)) continue;
            $port=intval($port);
            if($port<=0) continue;
            $proc=[...$proc, ...Manager::portCheck($port)];
        }
        $proc_data=array_column(Manager::getProcess(null, array_column($proc, 'PID')), 'ExecutablePath', 'ProcessId');
        self::_print($sets['/csv'], 'PROTO', 'PORT', 'IP', 'PID', 'PATH');
        foreach($proc as $info){
            self::_print($sets['/csv'], $info['Proto'],$info['LocalPort'],$info['LocalIP'],$info['PID'],$proc_data[$info['PID']]??'');
        }
    }

    /**
     * @xparams [/csv] [-pids #,#,#,...] [-n "nginx.exe,php-cgi-spawner.exe,php-cgi.exe,php.exe,php-win.exe,MultiPHPCGI.exe,..."]
     * @note Obtiene la lista de procesos en ejecución de esta instalación de MultiPHPCGI
     * @return void
     */
    function myprocess(...$params){
        $sets=self::_sets(['/csv', '-n', '-pids'], $params);
        $list=Manager::getProcessMyDir(array_filter(explode(',', $sets['-n'])), array_filter(explode(',', $sets['-pids']), 'is_numeric'));
        self::_print($sets['/csv'], 'PID', 'ParentPID', 'EXE', 'CMD');
        foreach($list as $item){
            self::_print($sets['/csv'], $item['ProcessId'], $item['ParentProcessId'], $item['Name'], $item['CommandLine']);
        }
    }

    /**
     * @xparams [/csv] [-pids #,#,#,...] [-n "nginx.exe,php-cgi-spawner.exe,php-cgi.exe,php.exe,php-win.exe,MultiPHPCGI.exe,..."]
     * @note Obtiene la lista de procesos en ejecución incluso fuera de MultiPHPCGI
     * @return void
     */
    function process(...$params){
        $sets=self::_sets(['/csv', '-n', '-pids'], $params);
        $list=Manager::getProcess(array_filter(explode(',', $sets['-n'])), array_filter(explode(',', $sets['-pids']), 'is_numeric'));
        self::_print($sets['/csv'], 'PID', 'ParentPID', 'EXE', 'CMD');
        foreach($list as $item){
            self::_print($sets['/csv'], $item['ProcessId'], $item['ParentProcessId'], $item['Name'], $item['CommandLine']);
        }
    }

    /**
     * @return void
     */
    protected function test_ini($ver=null){
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
    }
};
try{
    if(!isset($argv[1])){
        $cli->help();
        return;
    }
    $opt=$argv[1];
    if($opt=='-i-'){
        $cli->interative=true;
        $cli->list_opt[]='exit';
        $cli->list_opt[]='x';
        while(true){
            Manager::cli_autocomplete($cli->list_opt);
            echo "\n";
            $opt=Manager::cli_confirm(_BLUE."> "._DEF, null, '', 1);
            readline_add_history($opt);
            $params=preg_split('/\s+(?=(?:[^\"]*\"[^\"]*\")*[^\"]*$)/', $opt)?:[];
            $opt=array_shift($params);
            $params=preg_replace('/^"(.*)"$/', '$1', $params);
            if(in_array(strtolower($opt), ['exit','x'])) exit;
            try{
                $cli->_opt($opt, ...$params);
            }catch(ResponseErr $e){
                file_put_contents('php://stderr', _YELL.$e->getMessage()._DEF);
            }catch(Throwable $e){
                file_put_contents('php://stderr', _BGRED.$e._DEF);
            }
        }
    }
    else{
        $params=array_slice($argv, 2);
        $cli->_opt($opt, ...$params);
    }
}catch(ResponseErr $e){
    file_put_contents('php://stderr', _YELL.$e->getMessage()._DEF);
    exit(1);
}catch(Throwable $e){
    file_put_contents('php://stderr', _BGRED.$e._DEF);
    exit(1);
}