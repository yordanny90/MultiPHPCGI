<?php

class Manager{

    const PREFIX_SERVER_NAME='server.';
    const PREG_SERVER_NAME='/^server\.(\w+)$/';

    /**
     * @return string
     * @throws ResponseErr
     */
    static function getNginxVer(){
        $nginx=strval(static::getConfig()['nginx'] ?? '');
        return $nginx;
    }

    static function getPhpDir(){
        list($ver, $_)=explode("\n", file_get_contents(APP_DIR_INC.'/phpdir.txt'), 2);
        return $ver;
    }

    /**
     * @return array
     * Array datos:
     * - nginx
     * - [server.*]
     *   - SSLPort
     *   - Port
     *   - CGIPort
     *   - CGIMaxProc
     *   - PHP
     *   - Root
     * @throws ResponseErr
     */
    static function getConfig(): array{
        $config=parse_ini_file(self::config_file(), true, INI_SCANNER_RAW);
        if($config===false) throw new ResponseErr('Error al leer config.ini');
        return $config;
    }

    static function config_file(){
        $file=APP_DIR_USR.'\config.ini';
        if(!is_file($file)) copy(APP_DIR_INC.'\config.ini', $file);
        return $file;
    }

    /**
     * @param array|null $config
     * @return array|null
     * @throws ResponseErr
     */
    static function getServer_list(?array $config=null){
        $config??=self::getConfig();
        $servers=array_filter($config, function($val, $key){
            return is_array($val) && preg_match(self::PREG_SERVER_NAME, $key);
        }, ARRAY_FILTER_USE_BOTH);
        return $servers;
    }

    static function setConfig(string $file, array $config): bool{
        ob_start();
        foreach(array_filter($config, 'is_array') as $key=>$vars){
            echo PHP_EOL.'['.$key.']'.PHP_EOL;
            foreach($vars as $k=>$v){
                if(is_bool($v)){
                    echo $k.'='.$v?'true':'false'.PHP_EOL;
                }
                elseif(is_numeric($v)){
                    echo $k.'='.$v.PHP_EOL;
                }
                elseif(is_scalar($v)){
                    echo $k.'="'.addslashes($v).'"'.PHP_EOL;
                }
            }
        }
        $ini=ob_get_clean();
        return file_put_contents($file, $ini);
    }

    static function data2ini(array $data): string{
        $out='';
        foreach($data as $k=>$v){
            if(is_bool($v)){
                $out.=$k.'='.($v?'On':'Off').PHP_EOL;
            }
            elseif(is_numeric($v)){
                $out.=$k.'='.$v.PHP_EOL;
            }
            elseif($v!==null){
                $v=strval($v);
                if(preg_match('/[\s\;]/', $v)){
                    $out.=$k.'="'.addslashes($v).'"'.PHP_EOL;
                }
                else{
                    $out.=$k."=".addslashes($v).PHP_EOL;
                }
            }
        }
        return $out;
    }

    static function preg_list(array $data): string{
        return implode('|', array_map('preg_quote', array_filter($data, 'is_scalar')));
    }

    /**
     * Actualiza un archivo php.ini
     * @param string $origin
     * @param array $update
     * @param array $comment
     * @param array $uncomment
     * @param string|null $dest
     * @param $verbose
     * @return bool|resource|null
     */
    static function update_ini(string $origin, array $update=[], array $comment=[], array $uncomment=[], ?string $dest=null, $verbose=false){
        $orig=fopen($origin, 'r+');
        if(!$orig) $orig=fopen($origin, 'w+');
        if(!$orig) return null;
        $buffer=tmpfile();
        if(!$buffer) return null;
        $sets=array_filter($update, 'is_scalar');
        $com=self::preg_list($comment);
        $uncom=self::preg_list($uncomment);
        $keySets=array_keys($sets);
        $update=array_filter($update, 'is_array');
        $comment=array_map([
            self::class,
            'preg_list'
        ], array_filter($comment, 'is_array'));
        $uncomment=array_map([
            self::class,
            'preg_list'
        ], array_filter($uncomment, 'is_array'));
        $saved=0;
        $posA=0;
        $groupV='';
        while(!is_bool($pos=ftell($orig)) && is_string($line=fgets($orig))){
            if(trim($line)===''){
                $posA=$pos;
                continue;
            }
            if(preg_match('/^\s*\;/', $line)){
                $posA=$pos;
                if($uncom && preg_match('/^\;('.$uncom.')\s*(.|$)/', $line, $match)){
                    if(($match[2]=='=' && strstr($match[1], '=')===false) || (in_array($match[2], [
                                '',
                                ';'
                            ]) && strstr($match[1], '=')!==false)){
                        $posB=ftell($orig);
                        $length=$posA-$saved;
                        if($length>0){
                            if(fseek($orig, $saved)!=0 || stream_copy_to_stream($orig, $buffer, $length, $saved)===false) return null;
                        }
                        $out=substr($line, 1);
                        if($verbose){
                            echo $groupV.$out;
                            $groupV='';
                        }
                        if(fwrite($buffer, $out)===false) return null;
                        unset($out);
                        $saved=$posA=$posB;
                        if(fseek($orig, $saved)!==0) return null;
                        continue;
                    }
                }
                continue;
            }
            if(preg_match('/^\s*\[([^\];]*)\]/', $line, $match)){
                $posB=ftell($orig);
                $group=$match[1];
                $length=$posA-$saved;
                if($length>0){
                    if(fseek($orig, $saved)!=0 || stream_copy_to_stream($orig, $buffer, $length, $saved)===false) return null;
                }
                $out=self::data2ini($sets);
                if($out!==''){
                    if($verbose){
                        echo $groupV.$out;
                    }
                    if(fwrite($buffer, $out)===false) return null;
                }
                unset($out);
                $groupV='['.$group.']'.PHP_EOL;
                $com=$comment[$group] ?? null;
                $uncom=$uncomment[$group] ?? null;
                if(isset($update[$group])){
                    $sets=$update[$group];
                    $keySets=array_keys($sets);
                    unset($update[$group]);
                }
                else{
                    $sets=$keySets=[];
                }
                $saved=$posA;
                $posA=$posB;
                $length=$posA-$saved;
                if($length>0){
                    if(fseek($orig, $saved)!=0 || stream_copy_to_stream($orig, $buffer, $length, $saved)===false) return null;
                }
                $saved=$posA=$posB;
                if(fseek($orig, $saved)!==0) return null;
                continue;
            }
            if($com && preg_match('/^('.$com.')\s*(.|$)/', $line, $match)){
                if(($match[2]=='=' && strstr($match[1], '=')===false) || (in_array($match[2], [
                            '',
                            ';'
                        ]) && strstr($match[1], '=')!==false)){
                    $posA=$pos;
                    $posB=ftell($orig);
                    $length=$posA-$saved;
                    if($length>0){
                        if(fseek($orig, $saved)!=0 || stream_copy_to_stream($orig, $buffer, $length, $saved)===false) return null;
                    }
                    $out=';'.$line;
                    if($verbose){
                        echo $groupV.$out;
                        $groupV='';
                    }
                    if(fwrite($buffer, $out)===false) return null;
                    unset($out);
                    $saved=$posA=$posB;
                    if(fseek($orig, $saved)!==0) return null;
                    continue;
                }
            }
            if($keySets && preg_match('/^\s*([^=]+)\s*=\s*/', $line, $match) && in_array($match[1], $keySets)){
                $posA=$pos;
                $posB=ftell($orig);
                $length=$posA-$saved;
                if($length>0){
                    if(fseek($orig, $saved)!=0 || stream_copy_to_stream($orig, $buffer, $length, $saved)===false) return null;
                }
                $out=self::data2ini([
                    $match[1]=>$sets[$match[1]] ?? null,
                ]);
                unset($sets[$match[1]]);
                if($out!==''){
                    if($verbose){
                        echo $groupV.$out;
                        $groupV='';
                    }
                    if(fwrite($buffer, $out)===false) return null;
                }
                unset($out);
                $saved=$posA=$posB;
                if(fseek($orig, $saved)!==0) return null;
                continue;
            }
        }
        $length=ftell($orig)-$saved;
        if($length>0){
            if(fseek($orig, $saved)!=0 || stream_copy_to_stream($orig, $buffer, null, $saved)===false) return null;
        }
        $out=self::data2ini($sets);
        if($out!==''){
            $out=PHP_EOL.$out;
            if($verbose){
                echo $groupV.$out;
            }
            if(fwrite($buffer, $out)===false) return null;
        }
        foreach($update as $group=>$s){
            $out=self::data2ini($s);
            if($out!==''){
                $out=PHP_EOL.'['.$group.']'.PHP_EOL.$out;
                if($verbose) echo $out;
                if(fwrite($buffer, $out)===false) return null;
            }
        }
        unset($out);
        fseek($buffer, 0);
        fclose($orig);
        if($dest!==null){
            $success=false;
            if($dest=fopen($dest, 'w+')){
                $success=stream_copy_to_stream($buffer, $dest)!==false;
                fclose($dest);
                fseek($buffer, 0);
            }
            fclose($buffer);
            return $success;
        }
        return $buffer;
    }

    static function csvToAssoc(string $str): array{
        $rows=explode("\n", $str);
        $keys=str_getcsv(array_shift($rows), ',', '"', "\"");
        $rows=array_map(function($str) use ($keys){
            $values=str_getcsv($str, ',', '"', "\"");
            if(count($values)!=count($keys)) return null;
            return array_combine($keys, $values) ?? null;
        }, $rows);
        return array_filter($rows);
    }

    static function getProcessMyDir($name=null, $pid=null): array{
        $eq=[];
        if($name){
            $eq['Name']=$name;
        }
        if($pid){
            $eq['ProcessId']=$pid;
        }
        return EasyCLI::windows_process_list($eq, null, [
            'ExecutablePath'=>ROOT_DIR.'\\'
        ]);
    }

    static function getProcessNotMyDir($name=null): array{
        $eq=[];
        if($name){
            $eq['Name']=$name;
        }
        return EasyCLI::windows_process_list($eq, null, null, [
            'ExecutablePath'=>ROOT_DIR.'\\'
        ]);
    }

    /**
     * @param $ver
     * @return void
     */
    static function install_php($ver){
        if(!preg_match('/^\d+\.\d+\.\d+$/', $ver)) return;
        passthru('start /WAIT cmd /c install-php '.$ver);
    }

    /**
     * @param $ver
     * @return void
     */
    static function install_nginx(string $ver=''){
        if(!preg_match('/^\d+\.\d+\.\d+$/', $ver)) return;
        exec('start /WAIT cmd /c install-nginx.bat '.$ver);
    }

    /**
     * @param $ver
     * @return mixed
     * @throws ResponseErr
     */
    static function php_find_version($ver=null){
        if(!is_string($ver)) throw new ResponseErr('Debe ingresar una versión');
        if(!preg_match('/^\d+\.\d+$/', $ver)) throw new ResponseErr('Versión invalida');
        $list=Manager::php_list();
        preg_match_all('/\b'.preg_quote($ver).'\.(.+)\b/', implode("\n", $list), $m, PREG_SET_ORDER);
        $m=array_column($m, 0, 1);
        if(count($m)>1) ksort($m);
        $version=array_pop($m);
        if($version===null) throw new ResponseErr('Versión no encontrada');
        return $version;
    }

    /**
     * Convierte una versión simple a la versión completa más reciente según la lista de versiones completas
     *
     * Ejemplo: 7.4 => 7.4.33
     * @param string $ver
     * @param array $list_versiones
     * @return mixed|null
     */
    static function php_version_full(string $ver, array $list_versiones){
        if(!preg_match('/^\d+\.\d+$/', $ver)) return null;
        preg_match_all('/\b'.preg_quote($ver).'\.(.+)\b/', implode("\n", $list_versiones), $m, PREG_SET_ORDER);
        $m=array_column($m, 0, 1);
        if(count($m)>1) ksort($m);
        $version=array_pop($m);
        return $version;
    }

    static function php_version_simple_list(array $list_versiones){
        return array_unique(array_filter(array_map([self::class, 'php_version_simple'], $list_versiones)));
    }

    /**
     * Convierte una versión completa a la versión simple
     *
     * Ejemplo: 7.4.33 => 7.4
     * @param string $version
     * @return string|null
     */
    static function php_version_simple(string $version){
        if(!preg_match('/^(\d+\.\d+)\.\d+$/', $version, $m)) return null;
        return $m[1];
    }

    static function nginx_bin(string $ver, $install=false){
        $bin=APP_DIR_NGINX.'\\'.$ver.'\\nginx.exe';
        if(!is_file($bin) || !is_dir(APP_DIR_SITES)){
            if($install) self::install_nginx($ver);
            if(!is_file($bin)) return null;
        }
        return $bin;
    }

    static function phpcgi_bin($ver=null, $install=false){
        if($ver===null) $ver=self::getPhpDir() ?? null;
        $bin=APP_DIR_PHP.'\\'.$ver.'\\php-cgi.exe';
        if(!is_file($bin)){
            if($install) self::install_php($ver);
            if(!is_file($bin)) return null;
        }
        return $bin;
    }

    static function php_bin($ver=null, $install=false){
        if($ver===null) $ver=self::getPhpDir() ?? null;
        $bin=APP_DIR_PHP.'\\'.$ver.'\\php.exe';
        if(!is_file($bin)){
            if($install) self::install_php($ver);
            if(!is_file($bin)) return null;
        }
        return $bin;
    }

    static function php_ini($ver=null){
        if($ver===null) $ver=self::getPhpDir() ?? null;
        $bin=APP_DIR_PHP.'\\'.$ver.'\\php.ini';
        if(!is_file($bin)){
            return null;
        }
        return $bin;
    }

    static function php_list(){
        $list=array_filter(array_map(function($name){
            $php_cmd=APP_DIR_PHP.'\\'.$name.'\\php.exe';
            if(is_file($php_cmd)){
                return $name;
            }
            return null;
        }, scandir(APP_DIR_PHP)));
        return $list;
    }

    static function php_make_bat(){
        $list=self::php_list();
        $phpbat=function($delete=false){
            return array_filter(array_map(function($file)use($delete){
                if(!is_file($file)) return null;
                $name=basename($file);
                if(!preg_match('/^php(cgi)?\d+\.\d+(\.\d+)?\.bat$/', $name)) return null;
                if($delete) unlink($file);
                return $name;
            }, glob(APP_DIR_BIN.'/php*.bat')));
        };
        $phpbat(1);
        array_walk($list, function($v){
            $bin=self::phpcgi_bin($v);
            if($bin){
                file_put_contents(APP_DIR_BIN.'\\phpcgi'.$v.'.bat', "@echo off\n\"%~dp0..\php\\$v\\".basename($bin)."\" %*\n");
            }
            $bin=self::php_bin($v);
            if($bin){
                file_put_contents(APP_DIR_BIN.'\\php'.$v.'.bat', "@echo off\n\"%~dp0..\php\\$v\\".basename($bin)."\" %*\n");
            }
            return;
        });
        $list_simple=self::php_version_simple_list($list);
        array_walk($list_simple, function($v)use($list){
            $version=self::php_version_full($v, $list);
            $bin=self::phpcgi_bin($version);
            if($bin){
                file_put_contents(APP_DIR_BIN.'\\phpcgi'.$v.'.bat', "@echo off\n\"%~dp0..\php\\$version\\".basename($bin)."\" %*\n");
            }
            $bin=self::php_bin($version);
            if($bin){
                file_put_contents(APP_DIR_BIN.'\\php'.$v.'.bat', "@echo off\n\"%~dp0..\php\\$version\\".basename($bin)."\" %*\n");
            }
            return;
        });
        return $phpbat();
    }

    /**
     * @param string|null $version
     * @return array|array[]
     */
    static function getIPList(): array{
        $cmd='ipconfig | findstr /R "IPv[0-9] Address[\s\.]*:([\w:]+)"';
        $result=shell_exec($cmd);
        preg_match_all('/IPv. Address[\s\.]+:\s*([\w\:\.]+)\b/', $result?:'', $m);
        $list=[
            'IPv4'=>['127.0.0.1'],
            'IPv6'=>['::1'],
        ];
        foreach($m[1] as $ip){
            if(strpos($ip, ':')===false){
                $list['IPv4'][]=$ip;
            }
            else{
                $list['IPv6'][]=$ip;
            }
        }
        return $list;
    }

    /**
     * @return int
     * @throws ResponseErr
     */
    static function service_stop(){
        $list=self::getProcessMyDir([
            'php-cgi.exe',
            'php-cgi-spawner.exe',
            'nginx.exe'
        ]);
        if(count($list)>0){
            $list=array_column($list, 'ProcessId');
            if(!self::taskkill(...$list)) throw new ResponseErr('Fallo al detener los procesos');
        }
        return count($list);
    }

    static function php_stop(){
        $list=self::getProcessMyDir([
            'php.exe',
            'php-win.exe'
        ]);
        if(count($list)==0) return true;
        $mypid=getmypid();
        $list=array_column(array_filter($list, function($row) use ($mypid){
            return $row['ProcessId']!=$mypid && (strstr($row['CommandLine'], ROOT_DIR)!==false);
        }), 'ProcessId');
        if(!self::taskkill(...$list)) return false;
        return true;
    }

    static function addServer_conf(string $name, array $server){
        if(!preg_match(self::PREG_SERVER_NAME, $name)) return false;
        $data="\n[".$name."]\n";
        foreach($server As $n=>$v){
            if($n=='Root') $data.="; Raíz del servidor\n";
            if($n=='CGIMaxProc') $data.="; Procesos máximos de PHP-CGI\n";
            if($n=='Port') $data.="; URL http://localhost:$v/\n";
            if($n=='SSLPort') $data.="; URL https://localhost:$v/\n";
            $data.="$n=$v\n";
        }
        return file_put_contents(self::config_file(), $data, FILE_APPEND);
    }

    static function php_nts_list_online($reload=false){
        $zips=self::php_nts_list_online_file($reload);
        if(!$zips || !($r=fopen($zips, 'r'))) return null;
        $list=[];
        while(is_string($l=fgets($r))){
            $l=trim($l);
            $name=basename($l);
            if(preg_match('/php\-(\d+\.\d+\.\d+)\-/', $name, $m)) $list[]=$m[1];
        }
        return $list;
    }

    static function php_nts_list_online_file($reload=false){
        exec(APP_DIR_BIN.'/download_php_nts_list.bat '.($reload?'-f':''), $out);
        if(($f=array_pop($out)) && is_file(($f))){
            $f=realpath($f);
        }
        elseif(($f=array_shift($out)) && is_file(($f))){
            $f=realpath($f);
        }
        else{
            $f=null;
        }
        if(!$reload && $f && filemtime($f)<(time()-72000)){
            return self::php_nts_list_online_file(!$reload);
        }
        return $f;
    }

    /**
     * Proceso de registro de un nuevo servidor
     * @param string|null $_dir
     * @param string|null $_php
     * @return void
     * @throws ResponseErr
     */
    static function addServer(?string $_dir=null, ?string $_php=null){
        $servers=self::getServer_list();
        $maxPort=max(80, ...array_map('intval', array_filter(array_column($servers, 'Port'), 'is_numeric')));
        $maxSSLPort=max(8000+$maxPort, ...array_map('intval', array_filter(array_column($servers, 'SSLPort'), 'is_numeric')));
        $maxCGIPort=max(9000+$maxPort, ...array_map('intval', array_filter(array_column($servers, 'CGIPort'), 'is_numeric')));
        ++$maxPort;
        ++$maxSSLPort;
        ++$maxCGIPort;
        echo "Puertos: ".$maxPort.", ".$maxSSLPort.", ".$maxCGIPort."\n\n";
        $server=[
            'SSLPort'=>$maxSSLPort,
            'Port'=>$maxPort,
            'Root'=>$_dir ?? null,
            'PHP'=>$_php ?? self::getPhpDir(),
            'CGIPort'=>$maxCGIPort,
            'CGIMaxProc'=>8,
        ];
        if($server['Root'] && !is_dir($server['Root'])) $server['Root']=null;
        self::cli_autocomplete_filesystem(GLOB_ONLYDIR);
        $line=self::cli_confirm("Ingresa la dirección de Root (carpeta existente)", 'is_dir', $server['Root']);
        $server['Root']=realpath($line);
        echo "Ruta seleccionada: ".$server['Root']."\n\n";

        if(substr($server['Root'], 0, 2)===substr(ROOT_DIR, 0, 2)){
            $server['Root']=substr($server['Root'], 2);
        }
        $server['Root']=str_replace('\\', '/', $server['Root']);

        $line=self::cli_confirm("PHP versión ".$server['PHP']."\nDesea cambiar la versión de PHP?", ['Y','N'], 'Y');
        if($line=='Y'){
            $list=self::php_nts_list_online(true);
            $list_simple=self::php_version_simple_list($list);
            $line=self::cli_confirm("Ingrese la versión de PHP:", [...$list_simple, ...$list], $server['PHP']);
            $server['PHP']=$line;
            if(!in_array($line, $list)){
                $line=self::php_version_full($line, $list);
                if(!in_array($line, $list)) throw new ResponseErr('No se encuentra la versión completa para PHP '.$server['PHP']);
            }
            $server['PHP']=$line;
        }

        $server_list=array_keys($servers);
        echo "Servidores existentes: ".implode(", ", $server_list)."\n";

        $name=self::PREFIX_SERVER_NAME.preg_replace('/[^\w]+/', '_', basename($server['Root']));
        $suf='';
        $i=0;
        while(in_array($name.$suf, $server_list, true)){
            $suf='('.(++$i).')';
        }
        $name.=$suf;
        self::cli_autocomplete([self::PREFIX_SERVER_NAME,$name]);
        $line=self::cli_confirm("Ingrese un nombre (alfanumérico sin espacios) para el nuevo servidor. Sugerido: [".$name."]", function($v)use(&$server_list){
            if(!preg_match(self::PREG_SERVER_NAME, $v)){
                echo "* Formato inválido\n\n";
                return false;
            }
            if(in_array($v, $server_list, true)){
                echo "* El nombre ya existe\n\n";
                return false;
            }
            return true;
        });
        $name=$line;
        print_r($server);
        $line=self::cli_confirm("Desea guardar el nuevo servidor como [".$name."]?", ['Y','N'], 'Y');
        if($line=='N') return;
        self::addServer_conf($name, $server);

        $line=self::cli_confirm("Desea generar el conf del nuevo servidor ahora?", ['Y','N'], 'Y');
        if($line!='N'){
            passthru('mphpcgi.bat nginx-test');
            echo "Presione [ENTER] para continuar...";
            readline();
        }
    }

    /**
     * @param int $flags Parámetro para {@see glob()}
     * @return void
     */
    static function cli_autocomplete_filesystem(int $flags=0){
        readline_completion_function(function($v)use($flags){
            $l=glob(($v===''?'./':$v).'*', $flags)?:[''];
            return $l;
        });
    }

    /**
     * @param array|null $list
     * @param bool $case_sensitive
     * @return void
     */
    static function cli_autocomplete(?array $list=null, bool $case_sensitive=true){
        if(is_null($list) || !count($list)){
            $fn=function(){return [''];};
        }
        elseif($case_sensitive){
            $fn=function($v)use(&$list){
                $matches=[];
                foreach($list as $val){
                    if(stripos($val, $v)===0) $matches[]=$val;
                }
                if(!count($matches)) $matches[] = '';
                return $matches;
            };
        }
        else{
            $fn=function($v)use(&$list){
                $v=strtoupper($v);
                $matches=[];
                foreach($list as $val){
                    if(stripos(strtoupper($val), $v)===0) $matches[]=$val;
                }
                if(!count($matches)) $matches[] = '';
                return $matches;
            };
        }
        readline_completion_function($fn);
    }

    /**
     * @param string $msg
     * @param array|callable|null $filtro Solo imprime las opciones si es una array con 5 valores o menos
     * @param string|null $default
     * @param bool $case_sensitive
     * @return string
     * @throws ResponseErr
     */
    static function cli_confirm(string $msg, array|callable|null $filtro, ?string $default='', bool $case_sensitive=false){
        if(is_array($filtro) && !count($filtro)) $filtro=null;
        if(!is_array($filtro)) $case_sensitive=true;
        $default=$default ?? '';
        if(is_array($filtro)){
            $max=5;
            if(!$case_sensitive) $filtro=array_map('strtoupper', $filtro);
            $filtro=array_unique(array_map('trim', $filtro));
            if(count($filtro)<=$max) $msg.=" (".implode('/', $filtro).")";
            else $msg.=" (".implode('/', array_slice($filtro, 0, $max))." ...)";
            self::cli_autocomplete($filtro, $case_sensitive);
        }
        $msg.=" ".($default!==''?"[$default]":'')."\n";
        do{
            echo $msg;
            $line=trim(readline());
            if(!$case_sensitive) $line=strtoupper($line);
            if(is_array($filtro)){
                if(in_array($line, $filtro, true)) break;
            }
            elseif(is_callable($filtro)){
                if($filtro($line)) break;
            }
            if($default!=='' && $line===''){
                $line=$default;
                break;
            }
        }while(true);
        if(is_array($filtro)){
            self::cli_autocomplete();
        }
        return $line;
    }

    /**
     * @param $n
     * @return void
     */
    static function process_list($n=null){
        $names=[
            '*',
            'nginx.exe',
            'php-cgi-spawner.exe',
            'php-cgi.exe',
            'php.exe',
            'MultiPHPCGI.exe',
        ];
        $array2ini=function($name, $data)use(&$array2ini){
            if(is_object($data) || is_array($data)){
                echo "\n[$name]\n";
                foreach($data as $n=>$v){
                    $array2ini($n, $v);
                }
                return;
            }
            echo "  $name=$data\n";
        };
        if(is_numeric($n)) $n=$names[$n]??'';
        if($n=='*'){
            foreach(Manager::getProcessMyDir(null) as $p){
                $array2ini($p['Name'], $p);
            }
        }
        elseif(is_string($n) && $n!==''){
            foreach(Manager::getProcessMyDir($n) as $p){
                $array2ini($p['Name'], $p);
            }
        }
        else{
            echo "Opciones:\n";
            foreach($names as $k=>$name){
                echo "\t[".$k."] ".$name."\n";
            }
        }
    }

    /**
     * @param string|null $n
     * @return void
     * @throws ResponseErr
     */
    static function initServers(?string $n=null){
        $servers=self::getServer_list();
        if($n!==null){
            $name=$n;
            if(isset($servers[$name])){
                $saved=self::initServer($name, $servers[$name], true);
                if($saved) echo $name."\n";
            }
            return;
        }
        foreach($servers AS $name=>$server){
            $saved=self::initServer($name, $server);
            if($saved) echo $name."\n";
        }
    }

    /**
     * @param string $name
     * @param array $server
     * @param bool $replace
     * @return false|int
     * @throws ResponseErr
     */
    private static function initServer(string $name, array $server, bool $replace=false){
        $dest=APP_DIR_SITES.'/'.$name.'.conf';
        if((file_exists($dest) && filesize($dest)>0) && !$replace) return false;
        $tpl=file_get_contents(APP_DIR_INC.'/newserver.conf');
        if(!$tpl) throw new ResponseErr('newserver.conf no encontrado');
        $replace=[
            '{{Root}}'=>$server['Root']??null,
            '{{CGIPort}}'=>$server['CGIPort']??null,
        ];
        if(isset($server['Port'])){
            $replace['{{Port}}']=$server['Port'];
        }
        else{
            $replace['listen {{Port}} ']='# listen {{Port}} ';
            $replace['listen [::]:{{Port}} ']='# listen [::]:{{Port}} ';
        }
        if(isset($server['SSLPort'])){
            $replace['{{SSLPort}}']=$server['SSLPort'];
        }
        else{
            $replace['http2 on;']='# http2 on;';
            $replace['listen {{SSLPort}} ']='# listen {{SSLPort}} ';
            $replace['listen [::]:{{SSLPort}} ']='# listen [::]:{{SSLPort}} ';
        }
        $c=count($replace);
        $replace=array_filter($replace);
        if(count($replace)!=$c) throw new ResponseErr('Servidor "'.$name.'" inválido');
        $new=str_replace(array_keys($replace), array_values($replace), $tpl);
        mkdir(dirname($dest), 0777, true);
        $saved=file_put_contents($dest, $new);
        return $saved;
    }

    /**
     * @return array Lista de PIDs
     * @throws ResponseErr
     */
    static function service_start(){
        self::service_stop();
        $ver=self::getNginxVer();
        $nginx=self::nginx_bin($ver, true);
        if(!$nginx) throw new ResponseErr("NGINX $ver no encontrado");
        $cli=EasyCLI::newCleanEnv('', ROOT_DIR);
        shell_exec('mphpcgi.bat init-servers');
        $servers=self::getServer_list();
        $cmds=[];
        foreach($servers as $name=>$server){
            $cgiport=$server['CGIPort'] ?? null;
            if(!is_numeric($cgiport)) throw new ResponseErr('['.$name.'] CGIPort inválido: '.$cgiport);
            $phpbin=self::phpcgi_bin($server['PHP'] ?? null, true);
            if(!$phpbin) throw new ResponseErr('['.$name.'] PHP no encontrado: '.$server['PHP']);
            $maxProc=$server['CGIMaxProc'] ?? 8;
            if(!is_numeric($maxProc)) throw new ResponseErr('['.$name.'] CGIMaxProc inválido: '.$maxProc);
            $cmds[$name]=[
                'hidec',
                'php-cgi-spawner',
                $phpbin.' -d opcache.cache_id=mphpcgi-'.$name,
                $cgiport,
                '0+'.$maxProc,
            ];
        }
        $cmds['nginx']=[
            'hidec',
            $nginx,
            '-p',
            APP_DIR_USR.'\\conf-nginx-'.$ver,
        ];
        $pids=[];
        $fail=false;
        foreach($cmds As $name=>$cmd){
            $cli->set_cmd($cmd);
            $proc=$cli->open();
            if(!$proc){
                $fail=$name;
                break;
            }
            $pids[$name]=$proc->pid();
        }
        if($fail){
            self::taskkill(...$pids);
            throw new ResponseErr('Proceso fallido: '.$name);
        }
        return $pids;
    }

    /**
     * @return bool
     * @throws ResponseErr
     */
    static function nginx_test(){
        $ver=self::getNginxVer();
        $nginx=self::nginx_bin($ver, true);
        if(!$nginx) throw new ResponseErr('NGINX no instalado');
        shell_exec('mphpcgi.bat init-servers');
        $cmd=[
            $nginx,
            '-t',
            '-p',
            APP_DIR_USR.'\\conf-nginx-'.$ver,
        ];
        $procNginx=EasyCLI::newCleanEnv($cmd, ROOT_DIR)->open();
        if(!$procNginx) return false;
        if($procNginx->await()) $procNginx->terminate();
        $procNginx->out_passthru();
        $procNginx->err_passthru();
        return true;
    }

    /**
     * @return void
     * @throws ResponseErr
     */
    static function nginx_log_clear(){
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

    static function taskkill(...$pid){
        if(count($pid)==0) return false;
        $list=[];
        foreach($pid as $p){
            if(!is_numeric($p)) return false;
            $list[]='/PID '.$p;
        }
        $res=shell_exec('taskkill /F '.implode(' ', $list).' /T');
        if(!is_string($res)) return false;
        return strstr($res, 'SUCCESS')!==false;
    }

    static function portCheck(int $port){
        exec('netstat -ano | find ":'.$port.' " | find "LISTEN"', $out);
        $out=array_map(function($v){
            preg_match_all('/\s*([^\s]+)(\s|$)/', trim($v), $m);
            return array_combine(['Proto', 'Local Address', 'Foreign Address', 'State', 'PID'], $m[1]);
        }, $out);
        return $out;
    }
}