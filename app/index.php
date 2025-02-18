<?php
require __DIR__.'/init.php';
ini_set('default_charset', 'utf-8');
set_time_limit(20);
$bindirphp=ROOT_DIR.'/bin/php';
class index{
    private static function tpl($buffer){
        ?>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>MultiPHPCGI</title>
            <link rel="icon" type="image/svg+xml" sizes="any" href="/favicon.svg">
            <link type="text/css" rel="stylesheet" href="/assets.php/style.css?v=<?=APP_VER?>">
            <script type="text/ecmascript" src="/assets.php/app.js?v=<?=APP_VER?>"></script>
        </head>
        <body>
            <div id="menu">
                <a href="index.php" title="Inicio">🏠</a>
                <a href="index.php?op=config" title="Configuración del servicio">Config</a>
                <a href="index.php?op=info" title="Innformación">Info</a>
                <a href="index.php?op=phpini" title="Archivo de configuración de php">php.ini</a>
                <a href="index.php?op=appini" title="Archivo de configuración del servicio">app.ini</a>
                <a href="index.php?op=proclist" title="Lista de procesos en ejecución">Process</a>
                <a href="index.php?op=iplist" title="Lista de IP de este dispositivo">IP List</a>
            </div>
            <div id="content"><?=$buffer?></div>
        </body>
        <?php
        exit;
    }

    static function GET_(){
        ?>
        <p>Aqui podrá configurar su servicio de PHP</p>
        <?php
    }

    static function GET_info(){
        $fn_tr=function($name, $value){
            echo '<tr><td>'.toHTML($name).'</td><td>'.toHTML($value).'</td></tr>';
        }
        ?>
        <table class="table block"><?php
            $fn_tr('sendmail_from', ini_get('sendmail_from'));
            $fn_tr('sendmail_path', ini_get('sendmail_path'));
            $fn_tr('APP_VER',APP_VER);
            $fn_tr('PID',getmypid());
            $fn_tr('PHP_VERSION',PHP_VERSION);
            $fn_tr('BINARY',PHP_BINARY);
            $fn_tr('ROOT_DIR',ROOT_DIR);
            $fn_tr('BASEDIR',BASEDIR);
            $fn_tr('INC_DIR',INC_DIR);
            $fn_tr('CONFIG_DIR',CONFIG_DIR);
            $fn_tr('PHP_SAPI',PHP_SAPI);
            $fn_tr('lock_pid',implode(',', Manager::lock_pid()));
            ?>
        </table>
        <?php
    }

    static function GET_phpini(){
        $file=Manager::php_ini();
        if(!$file){
            echo '<h1>Archivo no encontrado</h1>';
            return;
        }
        ?>
        <h4><?=toHTML($file)?></h4>
        <div>
            <div class="pre"><?=toHTML(file_get_contents($file))?></div>
        </div>
        <?php
    }

    static function GET_config(){
        $config=Manager::getConfig();
        ?>
        <div class="pre"><?=toHTML(print_r($config, 1))?></div>
        <?php
    }

    static function GET_appini(){
        $file=INI_FILE;
        ?>
        <h4><?=toHTML($file)?></h4>
        <div class="">
            <div class="pre"><?=toHTML(file_get_contents($file))?></div>
        </div>
        <?php
    }

    static function GET_iplist(){
        $config=Manager::getConfig();
        $suffixport='';
        if(is_numeric($config['nginx']['Port']??null)) $suffixport=':'.$config['nginx']['Port'];
        ?>
        <table class="table block">
            <?php
            $ipList=Manager::getIPList([
                'AddressFamily'=>'IPv4',
                'AddressState'=>'Preferred',
            ]);
            foreach($ipList as $i=>$row){
                if($i==0){
                    echo '<tr>';
                    foreach(array_keys($row) as $h){
                        echo '<th>'.htmlentities($h).'</th>';
                    }
                    echo '</tr>';
                }
                echo '<tr>';
                foreach($row as $h=>$v){
                    echo '<td>';
                    if($h=='IPAddress') echo '<a target="__'.$v.'" href="http://'.$v.$suffixport.'">'.htmlentities($v).'</a>';
                    else echo '<a>'.htmlentities($v).'</a>';
                    echo '</td>';
                }
                echo '</tr>';
            }
            ?>
        </table>
        <?php
    }

    static function GET_proclist(){
        $fn_table=function($proc, $secondary=false){
            $pid=getmypid();
            if(!$proc){
                echo '<p>No se encontraron procesos</p>';
                return;
            }
            echo '<table class="table block" style="'.($secondary ? 'margin-left: 25px; background-color: #d3d3d3;' : '').'">';
            $cols=null;
            foreach($proc as $row){
                if(!$cols){
                    $cols=array_keys($row);
                    echo '<tr>'.implode('', array_map(function($h){return '<th>'.htmlentities($h).'</th>';}, $cols)).'</tr>';
                }
                $style='';
                if($row['ProcessId']==$pid){
                    $style.='background: lightgreen; ';
                }
                echo '<tr style="'.$style.'">';
                foreach($cols as $c){
                    echo '<td style="'.($c=='ProcessId'?'font-weight: bold;':'').'">'.htmlentities($row[$c]??'').'</td>';
                }
                echo '</tr>';
            }
            echo '</table>';
        }
        ?>
        <h3>PHP</h3>
        <div>
            <?php
            $fn_table(Manager::getProcessMyDir('php.exe'));
            if($otros=Manager::getProcessNotMyDir('php.exe')){
                $fn_table($otros, true);
            }
            ?>
        </div>
        <h3>PHP-CGI</h3>
        <div>
            <?php
            $fn_table(Manager::getProcessMyDir('php-cgi.exe'));
            if($otros=Manager::getProcessNotMyDir('php-cgi.exe')){
                $fn_table($otros, true);
            }
            ?>
        </div>
        <h3>NGINX</h3>
        <div>
            <?php
            $fn_table(Manager::getProcessMyDir('nginx.exe'));
            if($otros=Manager::getProcessNotMyDir('nginx.exe')){
                $fn_table($otros, true);
            }
            ?>
        </div>
        <?php
    }

    public static function main(){
        $method=$_SERVER['REQUEST_METHOD'].'_'.($_GET['op']??'');
        if(method_exists(self::class, $method)){
            ob_start();
            self::$method();
            $buffer='';
            while(ob_get_level()>0){
                $buffer=ob_get_clean().$buffer;
            }
            self::tpl($buffer);
        }
        else{
            self::tpl('Operación no permitida');
        }
    }
}

if(php_sapi_name()=='cli'){
    Manager::service_start();
    exit;
}

index::main();