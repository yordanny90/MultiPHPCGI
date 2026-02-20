<?php

use MultiPHPCGI\Manager;

require_once __DIR__.'/__use.php';
?>
<html>
<head>
    <meta charset="utf-8">
    <title>MultiPHPCGI</title>
</head>
<body style="font-family: sans-serif">
<h1>Servidores:</h1>
<section>
    <ul>
        <?php
        foreach(Manager::getServer_list() as $name=>$server){
            $name=$server['Name'] ?? $name;
            $urls=[];
            if(isset($server['SSLPort'])) $urls['HTTPS']='https://'.$_SERVER['HTTP_HOST'].":".$server['SSLPort'];
            if(isset($server['Port'])) $urls['HTTP']='http://'.$_SERVER['HTTP_HOST'].":".$server['Port'];
            if($urls){
                echo '<li>'.$name.' <p>';
                foreach($urls as $n=>$url){
                    ?>
                    <a style="font-size: 1.3em; font-weight: bolder;" href="<?=$url?>">[<?=$n?>]</a> <?php
                }
                echo '</p></li>';
            }
        }
        ?>
    </ul>
</section>
</body>
</html>