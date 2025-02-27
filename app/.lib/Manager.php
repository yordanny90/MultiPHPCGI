<?php

class Manager{

    static function lock_file(){
        return BASEDIR.'\\app.lock';
    }

    static function lock_pid(){
        ob_start();
        readfile(self::lock_file());
        return array_filter(explode(",", ob_get_clean()), 'is_numeric');
    }

    /**
     * @return array
     * Array datos:
     * - php
     *   - Dir
     *   - PortList
     * - nginx
     *   - Dir
     *   - Port
     *   - SSLPort
     *   - SSLEnabled
     */
    static function getConfig(): array{
        $config=@parse_ini_file(INI_FILE, true, INI_SCANNER_TYPED)?:[];
        $keys=array_fill_keys([
            'php',
            'nginx'
        ], []);
        $config=array_filter($config, 'is_array');
        $config=array_merge($keys, array_intersect_key($config, $keys));
        $config['php']=array_merge([
            'Dir'=>null,
            'PortList'=>null,
        ], $config['php'] ?? []);
        $config['nginx']=array_merge([
            'Dir'=>null,
            'Port'=>null,
            'SSLPort'=>null,
            'SSLEnabled'=>null,
        ], $config['nginx'] ?? []);
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
        $comment=array_map([self::class, 'preg_list'], array_filter($comment, 'is_array'));
        $uncomment=array_map([self::class, 'preg_list'], array_filter($uncomment, 'is_array'));
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
                    if(($match[2]=='=' && strstr($match[1], '=')===false) || (in_array($match[2], ['', ';']) && strstr($match[1], '=')!==false)){
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
                $com=$comment[$group]??null;
                $uncom=$uncomment[$group]??null;
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
                if(($match[2]=='=' && strstr($match[1], '=')===false) || (in_array($match[2], ['', ';']) && strstr($match[1], '=')!==false)){
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
            'ExecutablePath'=>ROOT_DIR
        ]);
    }

    static function getProcessNotMyDir($name=null): array{
        $eq=[];
        if($name){
            $eq['Name']=$name;
        }
        return EasyCLI::windows_process_list($eq, null, null, [
            'ExecutablePath'=>ROOT_DIR
        ]);
    }

    static function cert_generate($name='localhost'){
        $proc=EasyCLI::newCleanEnv([
            'cmd',
            '/C',
            'call',
            BASEDIR.'\cert_generate.bat',
            escapeshellarg($name)
        ])->open();
        $proc->close();
    }

    static function nginx_bin($dir=null){
        if($dir===null) $dir=self::getConfig()['nginx']['Dir']??null;
        $bin=ROOT_DIR.'\\bin\\nginx\\'.$dir.'\\nginx.exe';
        if(!is_file($bin)) return null;
        return $bin;
    }

    static function hidec_bin(){
        $bin=ROOT_DIR.'\\bin\\hidec\\hidec.exe';
        if(!is_file($bin)) return null;
        return $bin;
    }

    static function php_bin($dir=null){
        if($dir===null) $dir=self::getConfig()['php']['Dir']??null;
        $bin=ROOT_DIR.'\\bin\\php\\'.$dir.'\\php.exe';
        if(!is_file($bin)) return null;
        return $bin;
    }

    static function phpcgi_bin($dir=null){
        if($dir===null) $dir=self::getConfig()['php']['Dir']??null;
        $bin=ROOT_DIR.'\\bin\\php\\'.$dir.'\\php-cgi.exe';
        if(!is_file($bin)) return null;
        return $bin;
    }

    static function php_ini($dir=null){
        if($dir===null) $dir=self::getConfig()['php']['Dir']??null;
        $bin=ROOT_DIR.'\\bin\\php\\'.$dir.'\\php.ini';
        if(!is_file($bin)) return null;
        return $bin;
    }

    static function php_bin_list(){
        $bindirphp=ROOT_DIR.'\\bin\\php';
        $list=array_filter(array_map(function($name) use ($bindirphp){
            $php_cmd=$bindirphp.'\\'.$name.'\\php.exe';
            if(is_file($php_cmd)){
                $php_cmd=realpath($php_cmd);
                PHPDetect::addCustom($php_cmd);
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
        $list=self::getProcessMyDir(['php-cgi.exe', 'nginx.exe']);
        if(count($list)==0) return true;
        $list=array_column($list, 'ProcessId');
        if(!self::taskkill(...$list)) return false;
        return true;
    }

    static function app_stop(){
        $pid=self::lock_pid();
        if(count($pid)==0) return true;
        $list=self::getProcessMyDir(['php.exe','php-win.exe'], $pid);
        if(count($list)!=count($pid)) return true;
        if(!self::taskkill(...$pid)) return false;
        return true;
    }

    static function php_stop(){
        $list=self::getProcessMyDir(['php.exe','php-win.exe']);
        if(count($list)==0) return true;
        $mypid=getmypid();
        $list=array_column(array_filter($list, function($row)use($mypid){
            return $row['ProcessId']!=$mypid && (strstr($row['CommandLine'], BASEDIR)!==false);
        }), 'ProcessId');
        if(!self::taskkill(...$list)) return false;
        return true;
    }

    static function service_start(){
        //TODO Intenta generar un php.ini si no existe
        //TODO Se deben modificar los valores de:
        // [PHP] extension_dir="ext"
        // [opcache] opcache.enable=1
        // [mail function] sendmail_path=
        // TODO Habilitar las extensiones de la lista del app.ini
        if(!self::service_stop()) return false;
        $phpcgi=self::phpcgi_bin();
        if(!$phpcgi) return false;
        $nginx=self::nginx_bin();
        if(!$nginx) return false;
        $cli=EasyCLI::newCleanEnv('', ROOT_DIR);
        $config=self::getConfig();
        $portList=array_filter(explode(',', $config['php']['PortList']??''), 'is_numeric');
        if(count($portList)==0) return false;
        $hidec=self::hidec_bin();
        foreach($portList as $port){
            $cmd=[
                $hidec,
                $phpcgi,
                '-b',
                '127.0.0.1:'.$port,
            ];
            $cli->set_cmd($cmd);
            $procPhp=$cli->open();
            if(!$procPhp) return false;
        }
        $cmd=[
            $hidec,
            $nginx,
            '-p',
            INC_DIR.'\\nginx',
        ];
        $cli->set_cmd($cmd);
        $procNginx=$cli->open();
        if(!$procNginx) return false;
        return true;
    }

    static function nginx_test(){
        $nginx=self::nginx_bin();
        if(!$nginx) return false;
        $cmd=[
            $nginx,
            '-t',
            '-p',
            INC_DIR.'\\nginx',
        ];
        $procNginx=EasyCLI::newCleanEnv($cmd, ROOT_DIR)->open();
        if(!$procNginx) return false;
        if($procNginx->await()) $procNginx->terminate();
        $procNginx->out_passthru();
        $procNginx->err_passthru();
        return true;
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