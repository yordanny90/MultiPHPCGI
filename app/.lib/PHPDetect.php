<?php
/**
 * Detecta el comando PHP segun la versión requerida
 */
class PHPDetect{
    /**
     * @var string[] Lista de posibles comandos PHP
     */
    private static $custom=[];
    /**
     * @var string|null Direccion del archivo caché
     */
    protected static $cache_file;

    private function __construct(){ }

    /**
     * @return void
     */
    public static function clearCustom(): void{
        self::$custom=[];
    }

    /**
     * @param string $php_cmd
     */
    public static function addCustom(string $php_cmd): void{
        if(!in_array($php_cmd, self::$custom)) self::$custom[]=$php_cmd;
    }

    /**
     * @param string $php_cmd
     */
    public static function delCustom(string $php_cmd): void{
        $pos=array_search($php_cmd, self::$custom);
        if($pos!==false){
            unset(self::$custom[$pos]);
            self::$custom=array_values(self::$custom);
        }
    }

    /**
     * @return string[]
     */
    public static function customList(): array{
        return self::$custom;
    }

    public static function getDefaultLinux(): ?string{
        return '/usr/bin/php';
    }

    public static function getCache(string $php_ver): ?string{
        if(is_string(self::$cache_file) && is_file(self::$cache_file)){
            $cache=include(self::$cache_file);
            if(!is_string($cache[$php_ver]??null)) return null;
            return $cache[$php_ver];
        }
        return null;
    }

    private static function setCache(string $php_ver, ?string $php_cmd){
        if(!is_string(self::$cache_file)) return false;
        if(is_file(self::$cache_file)){
            $cache=include(self::$cache_file);
            if(!is_array($cache)) $cache=[];
            if(($cache[$php_ver]??null)===$php_cmd) return true;
        }
        else{
            $cache=[];
        }
        if(is_null($php_cmd)){
            unset($cache[$php_ver]);
        }
        else{
            $cache[$php_ver]=$php_cmd;
        }
        return DatasetExport::saveTo(self::$cache_file, $cache, 'Lista de comandos detectados por PHPDetect');
    }

    static function fileCache(?string $filename): void{
        if(is_string($filename)){
            self::$cache_file=$filename;
        }
        else{
            self::$cache_file=null;
        }
    }

    public static function getLinux(string $php_ver): ?string{
        if(!preg_match('/^(\d+\.\d+)\b(.*)?$/', $php_ver, $m)) return null;
        $ver=$m[1];
        return '/usr/bin/php'.$ver;
    }

    public static function getPlesk(string $php_ver): ?string{
        if(!preg_match('/^(\d+\.\d+)\b(.*)?$/', $php_ver, $m)) return null;
        $ver=$m[1];
        return '/opt/plesk/php/'.$ver.'/bin/php';
    }

    public static function getCpanel(string $php_ver): ?string{
        if(!preg_match('/^(\d+\.\d+)\b/', $php_ver, $m)) return null;
        $ver=str_replace('.', '', $m[1]);
        return '/opt/cpanel/ea-php'.$ver.'/root/usr/bin/php';
    }

    public static function getCloudlinux(string $php_ver): ?string{
        if(!preg_match('/^(\d+\.\d+)\b/', $php_ver, $m)) return null;
        $ver=str_replace('.', '', $m[1]);
        return '/opt/cloudlinux/alt-php'.$ver.'/root/usr/bin/php';
    }

    public static function autodetect(?string $php_ver=null): ?string{
        if(is_null($php_ver)) $php_ver=phpversion();
        $version=self::simple_version($php_ver);
        $cli=EasyCLI::newCleanEnv('php');
        $php_cmd=static::getCache($version);
        if(is_string($php_cmd) && static::_version_cli($cli, $php_cmd)===$version) return $php_cmd;
        $php_cmd=self::_autodetect($cli, $version);
        if(is_string($php_cmd)) self::setCache($version, $php_cmd);
        return $php_cmd;
    }

    public static function simple_version(?string $version){
        if(!is_string($version)) return $version;
        return implode('.',array_slice(explode('.',$version),0,2));
    }

    private static function _autodetect(EasyCLI $cli, $version){
        $php_cmd=defined('PHP_BINARY')?PHP_BINARY:null;
        if(is_string($php_cmd) && static::_version_cli($cli, $php_cmd)===$version) return $php_cmd;
        foreach(static::customList() AS $php_cmd){
            if(static::_version_cli($cli, $php_cmd)===$version) return $php_cmd;
        }
        $php_cmd='php';
        if(static::_version_cli($cli, $php_cmd)===$version) return $php_cmd;
        $php_cmd=static::getDefaultLinux();
        if(is_string($php_cmd) && static::_version_cli($cli, $php_cmd)===$version) return $php_cmd;
        $php_cmd=static::getLinux($version);
        if(is_string($php_cmd) && static::_version_cli($cli, $php_cmd)===$version) return $php_cmd;
        $php_cmd=static::getPlesk($version);
        if(is_string($php_cmd) && static::_version_cli($cli, $php_cmd)===$version) return $php_cmd;
        $php_cmd=static::getCpanel($version);
        if(is_string($php_cmd) && static::_version_cli($cli, $php_cmd)===$version) return $php_cmd;
        $php_cmd=static::getCloudlinux($version);
        if(is_string($php_cmd) && static::_version_cli($cli, $php_cmd)===$version) return $php_cmd;
        return null;
    }

    private static function _version_cli(EasyCLI $cli, string $php_cmd, $simple_version=true): ?string{
        $cli->set_cmd([$php_cmd, '-v']);
        $proc=$cli->open();
        if(!$proc) return null;
        if($proc->await()) $proc->terminate();
        $out=$proc->out_read();
        $proc->close();
        if(is_string($out) && preg_match('/^PHP ([\d\.]+) \(cli\)/', $out, $m)){
            $ver=$m[1];
            if($simple_version) $ver=self::simple_version($ver);
            return $ver;
        }
        return null;
    }
}