<?php

$expires = 60*60*60*24*12;
header("Pragma: public", true);
header("Cache-Control: maxage=".$expires, true);
header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT', true);
header("Vary: Accept-Encoding");

$requestUri = explode("/", $_SERVER['REQUEST_URI']);
$requestUri = explode("?", end($requestUri));
$filename = "tmp/files/".current($requestUri);

$tag = md5(filemtime($filename));

header('Etag: '.$tag);

if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $tag){
    header('Not Modified', true, 304);
    die();
}

$encoding = null;

if( strpos($_SERVER["HTTP_ACCEPT_ENCODING"], 'x-gzip') !== false ){
    $encoding = 'x-gzip';
} else if( strpos($_SERVER["HTTP_ACCEPT_ENCODING"],'gzip') !== false ){
    $encoding = 'gzip';
}

if(is_file($filename)){
    
    $tmp = explode(".", $filename);
    $ext = end($tmp);
    
    if($encoding != null && is_file($filename.".gz")){
        header("Content-Encoding: " . $encoding);
        $filename = $filename.".gz";
    }
    
    header("Content-Length: " . filesize($filename));
    header("Content-Type: text/" . ($ext == "js" ? "javascript" : "css") );
    fpassthru(fopen($filename, "r"));
    
} else {
    
    header("Not Found", true, 404);
    
}
