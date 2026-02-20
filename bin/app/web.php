<?php

use MultiPHPCGI\Manager;

require_once __DIR__.'/__use.php';
?>
<html>
<head>
    <meta charset="utf-8">
    <title>MultiPHPCGI</title>
</head>
<body style="font-family: sans-serif; font-size: 1.1em;">
<article>
    <h2>Servidores:</h2>
    <section>
        <ul>
            <?php
            foreach(Manager::getServer_list() as $name=>$server){
                $name=$server['Name'] ?? $name;
                $urls=[];
                if(isset($server['SSLPort'])) $urls['HTTPS']='https://'.$_SERVER['HTTP_HOST'].":".$server['SSLPort'];
                if(isset($server['Port'])) $urls['HTTP']='http://'.$_SERVER['HTTP_HOST'].":".$server['Port'];
                if($urls){
                    echo '<li>'.$name.' <br>';
                    foreach($urls as $n=>$url){
                        ?>
                        <a href="<?=$url?>">[<?=$n?>]</a> <?php
                    }
                    echo '</li><br>';
                }
            }
            ?>
        </ul>
    </section>
</article>
<article>
    <?php
    $ip_list=Manager::getIPList();
    ?>
    <h2>Lista de IPv4
        <a href="#" onclick="toggle(this.parentNode.nextElementSibling)">👁</a>
    </h2>
    <section style="display: none;">
        <ul>
            <?php
            foreach($ip_list['IPv4'] as $ip){
                $url=$_SERVER['REQUEST_SCHEME'].'://'.$ip.':'.$_SERVER['SERVER_PORT'];
                ?>
                <li>
                    <a href="<?=$url?>"><?=$ip?></a>
                </li>
                <?php
            }
            ?>
        </ul>
    </section>
    <h2>Lista de IPv6
        <a href="#" onclick="toggle(this.parentNode.nextElementSibling)">👁</a>
    </h2>
    <section style="display: none;">
        <ul>
            <?php
            foreach($ip_list['IPv6'] as $ip){
                $url=$_SERVER['REQUEST_SCHEME'].'://['.$ip.']:'.$_SERVER['SERVER_PORT'];
                ?>
                <li>
                    <a href="<?=$url?>"><?=$ip?></a>
                </li>
                <?php
            }
            ?>
        </ul>
    </section>
</article>
<script type="text/ecmascript">
    function toggle(node){
        node.style.display=node.style.display=='none'?'':'none';
    }
</script>
</body>
</html>