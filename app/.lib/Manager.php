<?php

class Manager{

    static function getNginxDir(){
        return file_get_contents(INC_DIR.'/nginxdir.txt');
    }

    static function getPhpDir(){
        return file_get_contents(INC_DIR.'/phpdir.txt');
    }

    /**
     * @return array
     * Array datos:
     * - server.*
     *   - Port
     *   - SSLPort
     *   - CGIPort
     *   - CGIMaxProc
     *   - PHP
     *   - Root
     */
    static function getConfig(): array{
        $config=@parse_ini_file(INI_FILE, true, INI_SCANNER_TYPED)?:[];
        $servers=array_filter($config, function($val, $key){
            return is_array($val) && preg_match('/^server[\-\.]\w+/', $key);
        }, ARRAY_FILTER_USE_BOTH);
        $keys=array_fill_keys([
        ], []);
        $config=array_filter($config, 'is_array');
        $config=array_merge($keys, array_intersect_key($config, $keys));
        $config['server']=$servers;
        return $config;
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
     * @param string $origin
     * @param array $update
     * @param string|null $dest
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
        $group=$groupV='';
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

    static function nginx_bin($dir=null){
        if($dir===null) $dir=self::getNginxDir();
        $bin=ROOT_DIR.'\\nginx\\'.$dir.'\\nginx.exe';
        if(!is_file($bin) || !is_dir(SITES_DIR)){
            self::install_nginx($dir);
            if(!is_file($bin)) return null;
        }
        return $bin;
    }

    static function install_php($dir){
        passthru('install-php '.$dir);
    }

    static function install_nginx($dir){
        passthru('install-nginx '.$dir);
    }

    static function php_bin($dir=null){
        if($dir===null) $dir=self::getPhpDir() ?? null;
        $bin=ROOT_DIR.'\\php\\'.$dir.'\\php.exe';
        if(!is_file($bin)){
            self::install_php($dir);
            if(!is_file($bin)) return null;
        }
        return $bin;
    }

    static function phpcgi_bin($dir=null){
        if($dir===null) $dir=self::getPhpDir() ?? null;
        $bin=ROOT_DIR.'\\php\\'.$dir.'\\php-cgi.exe';
        if(!is_file($bin)){
            self::install_php($dir);
            if(!is_file($bin)) return null;
        }
        return $bin;
    }

    static function php_ini($dir=null){
        if($dir===null) $dir=self::getPhpDir() ?? null;
        $bin=ROOT_DIR.'\\php\\'.$dir.'\\php.ini';
        if(!is_file($bin)){
            self::install_php($dir);
            if(!is_file($bin)) return null;
        }
        return $bin;
    }

    static function php_list(){
        $bindirphp=ROOT_DIR.'\\php';
        $list=array_filter(array_map(function($name) use ($bindirphp){
            $php_cmd=$bindirphp.'\\'.$name.'\\php.exe';
            if(is_file($php_cmd)){
                $php_cmd=realpath($php_cmd);
                return $name;
            }
            return null;
        }, scandir($bindirphp)));
        return $list;
    }

    static function getIPList(?array $like=null){
        $cmd='(Get-NetIPAddress';
        $where=[];
        if($like) foreach($like as $k=>$v){
            $where[]='$_.'.$k.' -like \''.$v.'\'';
        }
        if(count($where)>0){
            $cmd.=' | Where-Object { '.implode(' -and ', $where).' }';
        }
        $cmd.=' | Select-Object InterfaceAlias, IPAddress, AddressFamily, AddressState | ConvertTo-Csv -NoTypeInformation)';
        $result=shell_exec('powershell -Command '.escapeshellarg($cmd));
        $list=self::csvToAssoc($result);
        return $list;
    }

    static function service_stop(){
        $list=self::getProcessMyDir([
            'php-cgi.exe',
            'php-cgi-spawner.exe',
            'nginx.exe'
        ]);
        if(count($list)==0) return true;
        $list=array_column($list, 'ProcessId');
        if(!self::taskkill(...$list)) return false;
        return true;
    }

    static function php_stop(){
        $list=self::getProcessMyDir([
            'php.exe',
            'php-win.exe'
        ]);
        if(count($list)==0) return true;
        $mypid=getmypid();
        $list=array_column(array_filter($list, function($row) use ($mypid){
            return $row['ProcessId']!=$mypid && (strstr($row['CommandLine'], BASEDIR)!==false);
        }), 'ProcessId');
        if(!self::taskkill(...$list)) return false;
        return true;
    }

    static function addServer_conf(string $name, array $server){
        $data="\n[".$name."]\n";
        foreach($server As $n=>$v){
            if($n=='Root') $data.="; Raíz del servidor\n";
            if($n=='CGIMaxProc') $data.="; Procesos máximos de PHP-CGI\n";
            if($n=='Port') $data.="; URL http://localhost:$v/\n";
            if($n=='SSLPort') $data.="; URL https://localhost:$v/\n";
            $data.="$n=$v\n";
        }
        return file_put_contents(INI_FILE, $data, FILE_APPEND);
    }

    static function addServer(?string $_dir=null, ?string $_php=null){
        $config=self::getConfig();
        $maxPort=80;
        $maxSSLPort=8000;
        $maxCGIPort=9020;
        foreach($config['server'] AS $server){
            if(is_numeric($server['Port'])){
                $maxPort=max($maxPort, intval($server['Port']));
            }
            ++$maxPort;
            if(is_numeric($server['SSLPort'])){
                $maxSSLPort=max($maxSSLPort, intval($server['SSLPort']));
            }
            ++$maxSSLPort;
            if(is_numeric($server['CGIPort'])){
                $maxCGIPort=max($maxCGIPort, intval($server['CGIPort']));
            }
            ++$maxCGIPort;
        }
        $server=[
            'SSLPort'=>$maxSSLPort,
            'Port'=>$maxPort,
            'Root'=>$_dir ?? null,
            'PHP'=>$_php ?? self::getPhpDir(),
            'CGIPort'=>$maxCGIPort,
            'CGIMaxProc'=>8,
        ];
        do{
            echo "Ingresa la dirección de Root: ".(is_null($server['Root'])?'':"[{$server['Root']}]")."\n";
            $line = readline();
            if($line===''){
                if(is_null($server['Root'])) exit(1);
                $line=$server['Root'];
            }
            if(is_dir($line)){
                break;
            }
            else{
                echo "Debe ser una carpeta existente\n\n";
                continue;
            }
        }while(true);
        $server['Root']=realpath($line);
        if(substr($server['Root'], 0, 2)===substr(BASEDIR, 0, 2)){
            $server['Root']=substr($server['Root'], 2);
        }
        $server['Root']=str_replace('\\', '/', $server['Root']);

        do{
            echo "PHP versión ".$server['PHP']."\n";
            echo "Desea cambiar la versión de PHP? (Y/N): [N]\n";
            $line=strtoupper(trim(readline()));
        }while(!in_array($line, ['', 'Y', 'N']));
        if($line=='Y'){
            shell_exec('download_php_nts_list');
            exec('php-list-online', $list);
            $line='';
            do{
                echo implode("\n", array_filter($list, function($v)use($line){
                    return strpos($v, $line)===0;
                }));
                echo "\n";
                echo "Versión de PHP: ";
                $line=trim(readline());
            }while(!in_array($line, $list));
            $server['PHP']=$line;
        }

        $name='server.'.($server['SSLPort']);
        $suf='';
        $i=0;
        while(isset($config['server'][$name.$suf])){
            $suf='('.(++$i).')';
        }
        $name.=$suf;
        print_r($server);
        do{
            echo "Desea guardar el nuevo servidor como [".$name."]? (Y/N): [Y]\n";
            $line=strtoupper(trim(readline()));
        }while(!in_array($line, ['', 'Y', 'N']));
        if($line=='N') return;
        self::addServer_conf($name, $server);

        do{
            echo "Desea generar el conf del nuevo servidor ahora? (Y/N): [Y]\n";
            $line=strtoupper(trim(readline()));
        }while(!in_array($line, ['', 'Y', 'N']));
        if($line!='N'){
            passthru('init-servers');
            passthru('mphpcgi nginx-test');
            echo "...";
            readline();
        }
    }

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

    static function initServers(?string $n=null){
        $config=self::getConfig();
        foreach($config['server'] AS $name=>$server){
            if($n!==null && $name!==$n) continue;
            self::initServer($name, $server);
        }
    }

    private static function initServer(string $name, array $server, bool $replace=false){
        $dest=SITES_DIR.'/'.$name.'.conf';
        if(file_exists($dest) && !$replace) return false;
        $tpl=file_get_contents(INC_DIR.'/newserver.conf');
        if(!$tpl) throw new Exception('newserver.conf not found');
        $replace=[
            '{{Root}}'=>$server['Root']??null,
            '{{CGIPort}}'=>$server['CGIPort']??null,
        ];
        if(isset($server['Port'])){
            $replace['{{Port}}']=$server['Port'];
        }
        else{
            $replace['listen {{Port}} ']='# listen {{Port}} ';
        }
        if(isset($server['SSLPort'])){
            $replace['{{SSLPort}}']=$server['SSLPort'];
        }
        else{
            $replace['http2 on;']='# http2 on;';
            $replace['listen {{SSLPort}} ']='# listen {{SSLPort}} ';
        }
        $c=count($replace);
        $replace=array_filter($replace);
        if(count($replace)!=$c) throw new Exception('Server "'.$name.'" invalid');
        $new=str_replace(array_keys($replace), array_values($replace), $tpl);
        return file_put_contents($dest, $new);
    }

    static function service_start(){
        if(!self::service_stop()) return false;
        $nginx=self::nginx_bin();
        if(!$nginx) return false;
        $cli=EasyCLI::newCleanEnv('', ROOT_DIR);
        $config=self::getConfig();
        foreach($config['server'] as $server){
            $cgiport=$server['CGIPort'] ?? null;
            if(!is_numeric($cgiport)) return false;
            $phpserver=self::phpcgi_bin($server['PHP'] ?? null);
            if(!$phpserver) return false;
            $maxProc=$server['CGIMaxProc'] ?? 8;
            if(!is_numeric($maxProc)) return false;
            $cmd=[
                'hidec',
                'php-cgi-spawner',
                $phpserver.' -d opcache.cache_id=mphpcgi_'.$cgiport,
                $cgiport,
                '0+'.$maxProc,
            ];
            $cli->set_cmd($cmd);
            $procPhp=$cli->open();
            if(!$procPhp){
                return false;
            }
        }
        $cmd=[
            'hidec',
            $nginx,
            '-p',
            CONFIG_DIR.'\\nginx',
        ];
        $cli->set_cmd($cmd);
        $procNginx=$cli->open();
        if(!$procNginx){
            return false;
        }
        return true;
    }

    static function nginx_test(){
        $nginx=self::nginx_bin();
        if(!$nginx) return false;
        $cmd=[
            $nginx,
            '-t',
            '-p',
            CONFIG_DIR.'\\nginx',
        ];
        $procNginx=EasyCLI::newCleanEnv($cmd, ROOT_DIR)->open();
        if(!$procNginx) return false;
        if($procNginx->await()) $procNginx->terminate();
        $procNginx->out_passthru();
        $procNginx->err_passthru();
        return true;
    }

    static function nginx_log_clear(){
        if(!unlink($file=NGINX_LOG_DIR.'/access.log')) echo "Fail: $file\n";
        if(!unlink($file=NGINX_LOG_DIR.'/error.log')) echo "Fail: $file\n";
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

}