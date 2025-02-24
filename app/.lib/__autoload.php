<?php
spl_autoload_register(function($name){
    $namespace=array_filter(explode('\\', $name));
    $class=array_pop($namespace);
    $paths=[];
    $paths[]=__DIR__.'/'.implode('/', $namespace).'/'.$class.'.php';
    $paths[]=__DIR__.'/'.implode('-', $namespace).'/'.$class.'.php';
    $paths=array_unique($paths, SORT_STRING);
    foreach($paths AS &$path){
        if(is_file($path)){
            include $path;
            return;
        }
    }
});