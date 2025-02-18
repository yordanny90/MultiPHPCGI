<pre>
<?php
ob_start();
$allowed_exts=[
    'css'=>'text/css',
    'js'=>'application/javascript'
];
$file=__DIR__.'/assets'.($_SERVER['PATH_INFO'] ?? '');
$ext=pathinfo($file, PATHINFO_EXTENSION);
print_r($_SERVER);
if(!isset($allowed_exts[$ext]) || !file_exists($file)){
    http_response_code(404);
    exit("Archivo no encontrado");
}
// Cabeceras de caché
$max_age=86400; // 1 día
header("Content-Type: ".$allowed_exts[$ext]);
header('Connection: close',true);
header('Content-Length: '.(filesize($file)),true);
header("Cache-Control: public, max-age=".$max_age.", immutable");
header("Expires: ".gmdate("D, d M Y H:i:s", time()+$max_age)." GMT");
while(ob_get_level() > 0) ob_end_clean();
readfile($file);